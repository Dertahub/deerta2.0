<?php

namespace app\admin\controller\user;

use app\admin\model\keerta\Realname;
use app\common\controller\Backend;
use app\common\library\Auth;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit, $page, $alias, $bind, $refer_ids) = $this->buildparams();
            $where2 = [];
            $where3 = [];
            if($refer_ids){
                // 查找伞下
                $user_ids = \app\common\model\User::where("FIND_IN_SET({$refer_ids},refer_path)")->column('id');
//                $mark = \app\common\model\User::where('mark', 1)->column('id');
//                $user_ids = array_diff($user_ids, $mark);
                // 构建 where2 条件
                $where2 = [
                    'is_first' => 1,
                    'status' => 1,
                    'id' => ['in', $user_ids]
                ];
                $where3 = [
                    'id' => ['in', $user_ids]
                ];

            }
            $list = $this->model
//                ->with('realname')
                ->where($where)
                ->where($where3)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->realname = Realname::where('user_id', $v['id'])->value('surname') ??  '';
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
//        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

}
