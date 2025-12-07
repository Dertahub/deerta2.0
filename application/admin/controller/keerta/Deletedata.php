<?php

namespace app\admin\controller\keerta;

use app\admin\library\Auth;
use app\admin\model\Admin;
use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 清除数据
 *
 * @icon fa fa-circle-o
 */
class Deletedata extends Backend
{

    /**
     * Deletedata模型对象
     * @var \app\admin\model\keerta\Deletedata
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\keerta\Deletedata;

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
            if($params['tips'] != '确认执行删除操作'){
                throw new Exception('请输入正确的确认信息');
            }
            $admin = Admin::where('id',1)->find();

            if ($admin->password != (new Auth)->getEncryptPassword($params['password'], $admin->salt)) {
                throw new Exception('超级管理员密码错误');
            }
            $tableList = [];
            $list = \think\Db::query("SHOW TABLES");
            foreach ($list as $key => $row) {
                $tableList[reset($row)] = reset($row);
            }
            foreach ($tableList as $key => $value){
                Db::execute("DROP TABLE IF EXISTS `{$value}`");
            }
//            $result = $row->allowField(true)->save($params);
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
