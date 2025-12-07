<?php

namespace app\api\controller\xy;

use app\common\controller\Api;
use think\Config;

class Noticevideo extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        $this->model = new \app\admin\model\keerta\Noticevidao();
    }

    /**
     * 获取视频
     *
     */
    public function index()
    {
        $video = $this->model->where('id', 1)
            ->field('image,video')
            ->cache(true, 60)
            ->find();

        $video['image'] = $video['image'] ? cdnurl($video['image'], true) : '';
        $video['video'] = $video['video'] ? cdnurl($video['video'], true) : '';

        $this->success('获取成功', $video);
    }
}