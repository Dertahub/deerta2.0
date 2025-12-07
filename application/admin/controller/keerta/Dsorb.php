<?php

namespace app\admin\controller\keerta;

use app\admin\library\Auth;
use app\admin\model\Admin;
use app\admin\model\keerta\idle\Order;
use app\admin\model\keerta\order\Interest;
use app\admin\model\keerta\withdraw\Cny;
use app\admin\model\keerta\withdraw\Usdt;
use app\common\controller\Backend;
use app\common\model\MoneyLog;
use app\common\model\User;
use Exception;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 释放钱包
 *
 * @icon fa fa-circle-o
 */
class Dsorb extends Backend
{

    /**
     * Dsorb模型对象
     * @var \app\admin\model\keerta\Dsorb
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\keerta\Dsorb;

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        lock('dsorb', 120);
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            if($params['tips'] != '确认执行释放操作'){
                throw new Exception('请输入正确的确认信息');
            }
            $admin = Admin::where('id',1)->find();

            if ($admin->password != (new Auth)->getEncryptPassword($params['password'], $admin->salt)) {
                throw new Exception('超级管理员密码错误');
            }

            // 开始执行释放操作
            // 1.投资订单转释放钱包
            Interest::where('is_bouns', 0)
                ->chunk(100, function($bounsList) {
                    foreach ($bounsList as $item) {
                        $item->is_bouns = 1;
                        $item->save();

                        if ($item->type == 1){
                            $memo = '利息返还';
                        }elseif ($item->type == 2){
                            $memo = '购买产品赠送红包';
                        }elseif ($item->type == 3){
                            $memo = '分红';
                        }elseif ($item->type == 4){
                            $memo = '本金返还';
                        }
                        MoneyLog::money($item->user_id, $item->amount, 13, 'dsorb_money', $item->order_sn.'_'. $item->id, '投资订单转释放钱包('.$item['bouns_date'].$memo.')');
                    }
                });

            // 2.余额宝订单转释放钱包
            Order::where('status', 1)
                ->group('user_id')
                ->field('id,user_id,sum(amount) as total_amount,order_sn')
                ->chunk(100, function($xdlList) {
                    foreach ($xdlList as $item) {
                        Order::where('user_id', $item->user_id)->update(['status' => 2]);

                        MoneyLog::money($item->user_id, $item->total_amount, 13, 'dsorb_money', $item->order_sn, '余额宝订单转释放钱包');
                    }
                });

            // 3.提现未审核的全部驳回
            Cny::where('status', 0)
                ->group('user_id')
                ->field('id,user_id,sum(money) as total_amount,order_sn')
                ->chunk(100, function($cnyList) {
                    foreach ($cnyList as $item) {
                        Cny::where('user_id', $item->user_id)->update(['status' => 2,'reason'=>'转释放钱包驳回']);

                        MoneyLog::money($item->user_id, $item->total_amount, 13, 'dsorb_money', $item->order_sn, '提现中CNY待审核驳回');
                    }
                });

            // 获取实时汇率
            $usdtPrice = \app\admin\model\keerta\kline\Kline::where('id',1)
                ->cache(true, 60)
                ->value('price') ?? 7;
            Usdt::where('status', 0)
                ->group('user_id')
                ->field('id,user_id,sum(money) as total_amount,order_sn')
                ->chunk(100, function($usdtList) use($usdtPrice){
                    foreach ($usdtList as $item) {
                        Usdt::where('user_id', $item->user_id)->update(['status' => 2,'reason'=>'转释放钱包驳回']);

                        MoneyLog::money($item->user_id, bcmul($item->total_amount, $usdtPrice, 2), 13, 'dsorb_money', $item->order_sn, '提现中USDT待审核驳回');
                    }
                });

            // 用户的余额转入释放钱包
            User::where('id','>',0)
                ->chunk(100, function($userList) use($usdtPrice){
                    foreach ($userList as $item) {
                        // 余额转入，充值和提现加一起
                        $money = bcadd($item->money, $item->withdraw_money, 2);
                        $usdt = bcadd($item->usdt, $item->withdraw_usdt, 2);
                        $usdtMoney = bcmul($usdt, $usdtPrice, 2);
                        $totalMoney = bcadd($money, $usdtMoney, 2);
                        if($totalMoney > 0){
                            MoneyLog::money($item->id, $totalMoney, 13, 'dsorb_money', '', '用户余额转入释放钱包');

                            $item->money = 0;
                            $item->usdt = 0;
                            $item->withdraw_money = 0;
                            $item->withdraw_usdt = 0;
                            $item->save();
                        }
                    }
                });

//            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        unlock('dsorb');
        $this->success();
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edits($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            if($params['dsorb_ratio'] < 0 || $params['dsorb_ratio'] > 100){
                throw new Exception('请设置正确的比例，0~100之间');
            }
            if($params['time'] < 1 || $params['time'] > 23){
                throw new Exception('请设置正缺的时间格式，0~23之间');
            }

            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

}
