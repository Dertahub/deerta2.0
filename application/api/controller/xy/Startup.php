<?php

namespace app\api\controller\xy;

use app\common\controller\Api;

class Startup extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $detail = \app\admin\model\keerta\Startup::where('id', 1)
            ->field('id,pop_title,pop_content,title,content')
            ->find();

        $this->success('success', $detail);
    }

    /**
     * 启动页轮播
     *
     */
    public function banner()
    {
        $banner = \app\admin\model\keerta\Startup::where('id', 1)
            ->field('images')
            ->find();

        $images = explode(',', $banner['images']);
        foreach ($images as $key => $value){
            $images[$key] = cdnurl($value, true);
        }
        $banner['images'] = $images;
        $this->success('success', $banner);
    }

}