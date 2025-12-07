<?php

namespace app\admin\controller\kefu;

use think\Db;
use think\Exception;
use addons\kefu\library\Common;
use app\admin\model\kefu\KeFuKbs;
use think\exception\PDOException;
use app\common\controller\Backend;
use addons\kefu\library\StrComparison;
use think\exception\ValidateException;

/**
 * 知识库管理
 */
class Kbs extends Backend
{

    /**
     * KeFuKbs模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new KeFuKbs();
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function testMatch()
    {
        if ($this->request->isPost()) {
            $params = $this->request->only(['str1', 'str2']);
            if ($params['str1'] && $params['str2']) {
                $StrComparison = new StrComparison;
                $match         = $StrComparison->getSimilar($params['str1'], $params['str2']);
                $this->success('ok', null, $match);
            } else {
                $this->error('', null, 0);
            }
        }
        return $this->view->fetch();
    }

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        // $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->order($sort, $order)->count();

            $list = $this->model->where($where)->order($sort, $order)->limit($offset, $limit)->select();
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
                $params           = $this->preExcludeFields($params);
                $params['answer'] = Common::htmlImgUrlHandle($params['answer']);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
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
                $params           = $this->preExcludeFields($params);
                $params['answer'] = Common::htmlImgUrlHandle($params['answer']);

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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
