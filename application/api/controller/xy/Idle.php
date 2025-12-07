<?php

namespace app\api\controller\xy;

use app\admin\model\keerta\idle\Idleset;
use app\common\controller\Api;
use app\common\library\Auth;
use app\common\model\MoneyLog;
use think\Db;

class Idle extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 存入
     *
     */
    public function store()
    {
        $param = ['amount','pay_password'];
        $this->paramValidate($param);

        $begin = time();
        $uerId = $this->auth->id;
        $user = \app\common\model\User::where('id',$uerId)->find();
        lock('idle_store_'.$uerId, '请勿频繁操作', 60);
        $amount = $this->request->param('amount',0,'int');

        Db::startTrans();
        try{
            $pay_password = $this->request->param('pay_password', '', 'trim');
            if ((empty($pay_password))){
                throw new \Exception('请输入支付密码');
            }
            if ($user->pay_password != (new Auth)->getEncryptPassword($pay_password, $user->salt)) {
                throw new \Exception('支付密码错误！');
            }
            if($amount <= 0){
                throw new \Exception('请输入金额');
            }
            if($amount > $user->withdraw_money){
                throw new \Exception('余额不足');
            }
            $order_sn = 'L' . date('mdHi') . chr(rand(65, 90)) . rand(1, 9) . chr(rand(65, 90)) . rand(100, 999);
            MoneyLog::money($uerId, -$amount,6,'withdraw_money',$order_sn);

            $datArr = [
                'user_id' => $uerId,
                'amount' => $amount,
                'order_sn' => $order_sn,
                'status' => 1,
            ];
            \app\admin\model\keerta\idle\Order::create($datArr);

            $end = time();
            if($end - $begin > 5){
                throw new \Exception('请求超时');
            }
            Db::commit();
        }catch(\Exception $e){
            unlock('idle_store_'.$uerId);
            Db::rollback();
            $this->error($e->getMessage());
        }

        unlock('idle_store_'.$uerId);
        $this->success('存入成功');
    }
    /**
     * 列表
     */
    public function index()
    {
        $limit = $this->request->param('limit',10,'int');
        $user = $this->auth->getUser();
        $data['withdraw_money'] = $user->withdraw_money;

        $setting = Idleset::where('id',1)->find();
        $level = \app\admin\model\keerta\level\Level::where('id',$user->level_id)
            ->cache(true, 6)
            ->find();
        $data['day_profit_rate'] = bcadd($setting['day_profit_rate'], $level['small_rate'], 2);
        $data['total_profit_days'] = MoneyLog::where('user_id',$user['id'])
            ->where('type',7)
            ->cache(true, 6)
            ->count();

        $data['bj_amount'] = \app\admin\model\keerta\idle\Order::where('user_id',$user['id'])
            ->where('status',1)
            ->cache(true, 6)
            ->sum('amount');
        $data['list'] = \app\admin\model\keerta\idle\Order::where('user_id',$user['id'])
            ->where('status',1)
            ->paginate($limit);

        $this->success('成功',$data);
    }
    /**
     * 取出
     *
     */
    public function out()
    {
        $param = ['ids','pay_password'];
        $this->paramValidate($param);

        $begin = time();
        $uerId = $this->auth->id;
        lock('idle_withdraw_'.$uerId, '请勿频繁操作', 60);
        $ids = $this->request->param('ids',0,'int');
        if(!$ids){
            unlock('idle_withdraw_'.$uerId);
            $this->error('请选择转出金额');
        }
        Db::startTrans();
        try{
            $user = \app\common\model\User::where('id',$uerId)->find();
            $pay_password = $this->request->param('pay_password', '', 'trim');
            if ((empty($pay_password))){
                throw new \Exception('请输入支付密码');
            }
            if ($user->pay_password != (new Auth)->getEncryptPassword($pay_password, $user->salt)) {
                throw new \Exception('支付密码错误！');
            }
            $setting = Idleset::where('id',1)->find();
            if($ids > 0){
                $order = \app\admin\model\keerta\idle\Order::where('id',$ids)
                    ->where('user_id',$uerId)
                    ->where('status',1)
                    ->find();

                if(!$order){
                    throw new \Exception('没有可转出金额');
                }

                $span = \fast\Date::span($order->createtime, $begin, 'hours');
                if($span < $setting['time_limit']){
                    throw new \Exception('最低满'.$setting['time_limit'].'小时可转出');
                }
                $order->status = 2;
                $order->save();
                MoneyLog::money($uerId, $order->amount,6,'withdraw_money', $order->order_sn);
            }elseif($ids == -1){

                $order = \app\admin\model\keerta\idle\Order::where('user_id',$uerId)
                    ->where('status',1)
                    ->order('createtime desc, id desc')
                    ->find();
                if(!$order){
                    throw new \Exception('没有可转出金额');
                }

                $span = \fast\Date::span($order->createtime, $begin, 'hours');

                if($span < $setting['time_limit']){
                    throw new \Exception('最低满'.$setting['time_limit'].'小时可转出');
                }

                $total_amount = \app\admin\model\keerta\idle\Order::where('user_id',$uerId)
                    ->where('status',1)
                    ->sum('amount');

                MoneyLog::money($uerId, $total_amount,6,'withdraw_money', '');

                \app\admin\model\keerta\idle\Order::where('user_id',$uerId)
                    ->where('status',1)
                    ->update(['status'=>2]);
            }else{
                throw new \Exception('请选择转出金额');
            }

            $end = time();
            if($end - $begin > 5){
                throw new \Exception('请求超时');
            }

            Db::commit();
        }catch(\Exception $e){
            Db::rollback();
            unlock('idle_withdraw_'.$uerId);
            $this->error($e->getMessage());
        }

        unlock('idle_withdraw_'.$uerId);
        $this->success('操作成功');
    }


}