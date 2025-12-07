<?php

namespace app\api\controller\xy;
use app\common\controller\Api;

class Kline extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 获取实时汇率
     *
     */
    public function index()
    {
        $kline = \app\admin\model\keerta\kline\Kline::where('id',1)
            ->field('updatetime,price')
            ->find();
        if(!$kline){
            $this->error('暂无数据');
        }

        $this->success('success',$kline);
    }

}