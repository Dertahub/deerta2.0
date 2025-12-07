<?php

namespace app\api\controller\xy;

use app\common\controller\Api;

class Banner extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 轮播
     *
     */
    public function index()
    {
        $seat = $this->request->get('seat', 1, 'int');
        $list = \app\admin\model\keerta\Banner::where('seat', $seat)
            ->where('switch', 1)
            ->field('id,title,image,video,type,jump_method,url')
            ->order('weigh desc,id desc')
            ->limit(5)
            ->select();

        foreach ($list as &$item){
            $item['image'] = $item['image'] ? cdnurl($item['image'], true) : '';
            $item['video'] = $item['video'] ? cdnurl($item['video'], true) : '';
        }

        $this->success('获取成功', $list);
    }

}