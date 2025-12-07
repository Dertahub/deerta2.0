<?php

namespace app\admin\controller\keerta\redeem;

use app\common\controller\Backend;

/**
 * 兑换记录
 *
 * @icon fa fa-circle-o
 */
class Redeem extends Backend
{

    /**
     * Redeem模型对象
     * @var \app\admin\model\keerta\redeem\Redeem
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\keerta\redeem\Redeem;
        $this->view->assign("fromList", $this->model->getFromList());
        $this->view->assign("toList", $this->model->getToList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


}
