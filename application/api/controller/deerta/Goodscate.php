<?php

namespace app\api\controller\deerta;

use app\common\controller\Api;

class Goodscate extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 获取产品分类
     *
     */
    public function index()
    {
        $list = \app\admin\model\keerta\Goodscate::where('switch', 1)
            ->field('id,name')
            ->order('weigh desc, id desc')
            ->cache(true, 60)
            ->select();

        $this->success('success', $list);
    }
}