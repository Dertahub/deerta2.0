<?php

namespace app\admin\controller\kefu;

use think\Db;
use app\common\controller\Backend;

/**
 * KeFu配置
 */
class Config extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index()
    {
        $config     = Db::name('kefu_config')->column('name,value');
        $csr_config = Db::name('kefu_csr_config')
            ->alias('c')
            ->field('c.id,c.admin_id,c.ceiling,a.username')
            ->join('admin a', 'c.admin_id=a.id')
            ->select();

        $this->view->assign('config_list', $config);
        $this->view->assign('csr_config', $csr_config);
        return $this->view->fetch();
    }

    /**
     * 保存修改
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $config = Db::name('kefu_config')->column('name,value');
            $params = $this->request->post("row/a");
            if ($params) {
                // 配置更新-只更新有修改的
                foreach ($params as $key => $value) {
                    if ($value != $config[$key]) {
                        Db::name('kefu_config')->where('name', $key)->update(['value' => $value]);
                    }
                }
                $this->success();
            }
            $this->error();
        }
    }
}