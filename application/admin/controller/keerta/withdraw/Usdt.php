<?php

namespace app\admin\controller\keerta\withdraw;

use app\admin\model\keerta\Realname;
use app\common\controller\Backend;
use app\common\model\MoneyLog;
use app\common\model\User;
use Exception;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\response\Json;

/**
 * USDT提现
 *
 * @icon fa fa-circle-o
 */
class Usdt extends Backend
{

    /**
     * Usdt模型对象
     * @var \app\admin\model\keerta\withdraw\Usdt
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\keerta\withdraw\Usdt;
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit, $page, $alias, $bind, $refer_ids] = $this->buildparams();
        $total_money = 0;
        $total_count = 0;
        $where2 = [];
        if($refer_ids){
            // 查找伞下
            $user_ids = User::where("FIND_IN_SET({$refer_ids},refer_path)",'>',0)->column('id');
            $mark = \app\common\model\User::where('mark', 1)->column('id');
            $user_ids = array_diff($user_ids, $mark);
            // 构建 where2 条件
            $where2 = [
                'status' => 1,
                'user_id' => ['in', $user_ids]
            ];

            $total_money = $this->model
                ->where($where)
                ->where($where2)
                ->sum('money');
            $total_count = $this->model
                ->where($where)
                ->where($where2)
                ->count();
        }
        $list = $this->model
//            ->with(['realname','user'])
            ->where($where)
            ->where($where2)
            ->order($sort, $order)
            ->paginate($limit)->each(function ($item){
                $item['realname'] = Realname::where('user_id', $item['user_id'])->value('surname') ??  '';
                $item['mark'] = User::where('id', $item['user_id'])->value('mark') ??  '';
            });

        $result = [
            'total' => $list->total(),
            'rows' => $list->items(),
            'total_money' => $total_money,
            'total_count' => $total_count,
        ];
        return json($result);
    }
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
        lock('withdraw_money_'. $row['id'],'', 60);
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
            if($params['status'] == 2){
                MoneyLog::money($row['user_id'], $row['money'], 4, 'withdraw_usdt', $row['order_sn'], 1);
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
