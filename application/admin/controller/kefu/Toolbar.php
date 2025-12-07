<?php

namespace app\admin\controller\kefu;

use app\common\controller\Backend;
use app\admin\model\kefu\KefuToolbar;

/**
 * 窗口工具栏管理
 */
class Toolbar extends Backend
{

    /**
     * KefuToolbar模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new KefuToolbar();
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("PositionList", $this->model->getPositionList());
    }
}
