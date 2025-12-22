<?php

namespace app\admin\controller\keerta\recharge;

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
 * 余额充值
 *
 * @icon fa fa-circle-o
 */
class Money extends Backend
{

    /**
     * Money模型对象
     * @var \app\admin\model\keerta\recharge\Money
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\keerta\recharge\Money;
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
        $first_count = 0;
        $total_money2 = 0;
        $first_count2 = 0;
        $where2 = [];
        $where3 = [];
        if($refer_ids){
            // 查找伞下
            $user_ids = User::where("FIND_IN_SET({$refer_ids},refer_path)")->column('id');
            $mark = \app\common\model\User::where('mark', 1)->column('id');
            $user_ids = array_diff($user_ids, $mark);
            // 构建 where2 条件
            $where2 = [
                'is_first' => 1,
                'status' => 1,
                'user_id' => ['in', $user_ids]
            ];
            $where3 = [
                'status' => 1,
                'user_id' => ['in', $user_ids]
            ];

            $total_money = round($this->model
                ->where($where)
                ->where($where2)
                ->sum('money'), 2);
            $first_count = $this->model
                ->where($where)
                ->where($where2)
                ->count();

            $total_money2 = round($this->model
                ->where($where)
                ->where($where3)
                ->sum('money'), 2);
            $first_count2 = $this->model
                ->where($where)
                ->where($where3)
                ->count();

        }

        $list = $this->model
            ->where($where)
            ->where($where3)
            ->order($sort, $order)
            ->paginate($limit)->each(function ($item){
                $item['realname'] = Realname::where('user_id', $item['user_id'])->value('surname') ??  '';
                $item['mark'] = User::where('id', $item['user_id'])->value('mark') ??  '';
            });


        $result = [
            'total' => $list->total(),
            'rows' => $list->items(),
            'total_money' => $total_money,
            'first_count' => $first_count,
            'total_money2' => $total_money2,
            'first_count2' => $first_count2,
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
            if($params['status'] == 1){
                MoneyLog::money($row['user_id'], $row['money'], 1, 'money', $row['order_sn']);
                // 查找是否有真是首充
                if($row['is_first'] == 0){
                    $first_money = $this->model->where('user_id', $row['user_id'])
                        ->where('is_first', 1)
                        ->where('status', 1)
                        ->find();
                    if(empty($first_money)){
                        $params['is_first'] = 1;
                        $this->model->where('user_id', $row['user_id'])
                            ->where('is_first', 1)
                            ->update(['is_first' => 0]);
                    }
                }
            }else{
                if($row['is_first'] == 1){
                    $params['is_first'] = 0;
                }
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
