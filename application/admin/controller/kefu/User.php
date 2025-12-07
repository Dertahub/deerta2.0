<?php

namespace app\admin\controller\kefu;

use app\admin\model\kefu\KeFuUser;
use app\common\controller\Backend;

/**
 * 用户管理
 */
class User extends Backend
{

    /**
     * KeFuUser模型对象
     * @var KeFuUser
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new KeFuUser();

    }

    public function add()
    {
        return;
    }

    public function multi($ids = null)
    {
        return;
    }


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->with(['user'])->where($where)->order($sort, $order)->count();
            $list  = $this->model->with(['user'])->where($where)->order($sort, $order)->limit($offset, $limit)->select();
            foreach ($list as $row) {
                $row->getRelation('user')->visible(['nickname']);
            }
            $list = collection($list)->toArray();
            return json(["total" => $total, "rows" => $list]);
        }
        return $this->view->fetch();
    }
}
