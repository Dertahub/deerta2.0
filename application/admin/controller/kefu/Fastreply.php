<?php

namespace app\admin\controller\kefu;

use think\Db;
use think\Exception;
use addons\kefu\library\Common;
use think\exception\PDOException;
use app\common\controller\Backend;
use think\exception\ValidateException;
use app\admin\model\kefu\KeFuFastReply;

/**
 * 快捷回复管理
 */
class Fastreply extends Backend
{

    /**
     * KeFuFastReply模型对象
     */
    protected $model     = null;
    protected $dataLimit = 'auth';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new KeFuFastReply();
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign('isSuperAdmin', $this->auth->isSuperAdmin());
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
            $total = $this->model->with(['admin'])->where($where)->order($sort, $order)->count();

            $list = $this->model->with(['admin'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {
                $row->content = strip_tags($row->content);
                $row->getRelation('admin')->visible(['nickname']);
            }
            $list = collection($list)->toArray();
            return json(["total" => $total, "rows" => $list]);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {

                if (isset($params['is_general']) && $params['is_general']) {
                    $params['admin_id'] = false;
                } else {
                    $params['admin_id'] = true;
                }

                $params            = method_exists($this, 'preExcludeFields') ? $this->preExcludeFields($params) : $params;
                $params['content'] = Common::htmlImgUrlHandle($params['content']);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill && $params['admin_id']) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name     = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException|PDOException|Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {

                if (isset($params['is_general']) && $params['is_general'] && $this->auth->isSuperAdmin()) {
                    $params['admin_id'] = 0;
                } else {
                    $params['admin_id'] = $this->auth->id;
                }

                $params            = method_exists($this, 'preExcludeFields') ? $this->preExcludeFields($params) : $params;
                $params['content'] = Common::htmlImgUrlHandle($params['content']);

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name     = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException|PDOException|Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        if (!$row->admin_id && $this->auth->isSuperAdmin()) {
            $row->is_general = '1';
        } else {
            $row->is_general = '0';
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
