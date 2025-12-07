<?php

namespace app\api\controller\deerta;

use app\common\controller\Api;
use think\Config;

class Noticecate extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        $this->model = new \app\admin\model\keerta\Noticecate();
    }

    /**
     * 资讯分类列表
     */
    public function index()
    {
        $is_recommend = $this->request->get('is_recommend', 0, 'int');
        if ($is_recommend){
            $this->model->where('home', 1);
        }
        $list = $this->model->where('switch', 1)
            ->field('id,title,image')
            ->cache(true, 60)
            ->select();

        foreach ($list as &$item){
            $item['image'] = $item['image'] ? cdnurl($item['image'], true) : '';
        }

        $this->success('获取成功', $list);
    }
}