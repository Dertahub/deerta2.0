<?php

namespace app\admin\controller\keerta\withdraw;

use app\admin\model\keerta\Realname;
use app\common\controller\Backend;
use app\common\model\User;
use think\exception\DbException;
use think\response\Json;

/**
 * CNY提现
 *
 * @icon fa fa-circle-o
 */
class Cny extends Backend
{

    /**
     * Cny模型对象
     * @var \app\admin\model\keerta\withdraw\Cny
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\keerta\withdraw\Cny;
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

}
