<?php

namespace app\admin\controller\kefu;

use think\Db;
use app\common\controller\Backend;
use app\admin\model\kefu\KeFuBlacklist;

/**
 * 客服黑名单管理
 */
class Blacklist extends Backend
{

    /**
     * KeFuBlacklist模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new KeFuBlacklist();
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

            $total = $this->model->with(['admin', 'kefuuser'])->where($where)->order($sort, $order)->count();
            $list  = $this->model->with(['admin', 'kefuuser'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {
                $row->getRelation('admin')->visible(['nickname']);
                if ($row->kefuuser->user_id) {
                    $row->fu_user_nickname = Db::name('user')
                        ->where('id', $row->kefuuser->user_id)
                        ->value('nickname');
                }
            }
            $list = collection($list)->toArray();
            return json(["total" => $total, "rows" => $list]);
        }
        return $this->view->fetch();
    }
}
