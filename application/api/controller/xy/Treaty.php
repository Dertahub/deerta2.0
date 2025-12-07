<?php

namespace app\api\controller\xy;

use app\admin\model\treaty\Category;
use app\common\controller\Api;

class Treaty extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 获取合同
     *
     */
    public function info()
    {
        $order_sn = $this->request->param('order_sn','','trim');
        if (!$order_sn){
            $this->error("请输入订单号", "/");
        }
        $sign = \app\admin\model\keerta\treaty\Sign::where('order_sn', $order_sn)->find();
        if (!$sign){
            $this->error("合同不存在", "/");
        }
        $sign['official_seal_image'] = cdnurl($sign['official_seal_image'], true);
        $sign['official_seal_image2'] = cdnurl($sign['official_seal_image2'], true);
        $handwritten_signature = \app\admin\model\keerta\order\Order::where('order_sn', $order_sn)->value('handwritten_signature');
        $sign['handwritten_signature'] = $handwritten_signature ? cdnurl($handwritten_signature, true) : '';

        $this->success("获取成功", $sign);
    }

}