<?php

namespace app\api\controller\deerta;

use app\admin\model\keerta\recharge\Money;
use app\admin\model\keerta\recharge\Usdt;
use app\common\controller\Api;

class Recharge extends Api
{

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 提交充值
     */
    public function submit()
    {
        $userId = $this->auth->id;
        lock('recharge_' . $userId, '请稍后再试', 10);

        $param = ['money','image','type','memo'];

        $this->paramValidate($param);
        $user = $this->auth->getUser();
        if($user['realname_status'] != 2){
            $this->error('请先完成实名认证！');
        }
        $realname = \app\admin\model\keerta\Realname::where('user_id', $user['id'])
            ->where('status', 1)
            ->find();
        if(!$realname){
            $this->error('请先完成实名认证！');
        }

        $money = $this->request->post('money',0, 'float');

        $image = $this->request->post('image','','trim');
        if (!$image) {
            $this->error('请上传充值凭证');
        }

        $type = $this->request->post('type','money', 'trim');
        if (!in_array($type, ['money','usdt'])) {
            $this->error('充值类型错误');
        }

        $memo = $this->request->post('memo', '', 'trim');
        if ($type == 'money'){
            if ($money < 1) {
                $this->error('最低充值金额1元');
            }
            $model = new Money();

        }else{
            if ($money < 1) {
                $this->error('最低充值金额1U');
            }
            $model = new Usdt();
        }
        // 是否有正在审核中
        $order = $model->where('user_id', $userId)->where('status', 0)->find();
        if ($order) {
            $this->error('您有正在审核中的订单，请等待审核通过后再提交新订单');
        }

        $id = $this->getOrderId($model);

        $is_first = $model->where('user_id', $userId)->find();
        $is_first = $is_first ? 0 : 1;
        $dataArr = [
            'id' => $id,
            'user_id' => $userId,
            'money' => $money,
            'image' => $image,
            'status' => 0,
            'createtime' => time(),
            'order_sn' => \fast\Random::uuid(),
            'memo' => $memo,
            'is_first' => $is_first,
        ];
        $model->allowField(true)->save($dataArr);

        $this->success('提交成功,请等待审核');
    }

    /**
     * 生成唯一id 6位数
     *
     * @return string
     */
    public function getOrderId($model): string
    {
        $i = 0;
        do {
            $i++;
            if($i < 10){
                // 生成一个6位的随机字符串
                $id = rand(100000, 999999);
            }else{
                $id = rand(1000000, 9999999);
            }

            // 如果需要保证全局唯一，这里应进行数据库查询验证
            $existingCode = $model->where('id', $id)->find();

            // 若已存在相同地邀请码，则继续生成新地邀请码
        } while ($existingCode);

        return $id;
    }
}