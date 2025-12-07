<?php

namespace app\api\controller\deerta;

use app\common\controller\Api;
use think\Config;

class Line extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        $this->model = new \app\admin\model\keerta\Line;
    }
    /**
     * 获取线路列表
     *
     */
    public function index()
    {
        $list = $this->model->where('switch', 1)
            ->field('id,server_name,line_name,logo,domain')
            ->cache('line', 3600)
            ->select();
        foreach ($list as &$item){
            $item['logo'] = cdnurl($item['logo'],  true);
        }
        $this->success('获取成功', $list);
    }

}