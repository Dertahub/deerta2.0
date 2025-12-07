<?php

namespace app\api\controller\deerta;

use app\common\controller\Api;
use app\common\library\Auth;
use app\common\model\MoneyLog;
use think\Db;

class Redeem extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function cnyToUsdt()
    {
        $param = ['type','cny','pay_password'];
        $this->paramValidate($param);

        $type = $this->request->param('type', 'money', 'trim');
        if (!in_array($type, ['money', 'withdraw_money'])){
            $this->error('请选择正确的钱包类型！');
        }

        $cny = $this->request->param('cny','0', 'float');
        if ($cny < 1) {
            $this->error('请输入正确的CNY金额');
        }

        $user = $this->auth->getUser();
        $pay_password = $this->request->param('pay_password', '', 'trim');
        if ($user->pay_password != (new Auth)->getEncryptPassword($pay_password, $user->salt)) {
            $this->error('支付密码错误！');
        }
        lock('user_cnyToUsdt_'.$user->id, 10);
        if ($user->$type < $cny){
            $this->error('您的余额不足！');
        }
        lock('user_cnyToUsdt_'.$user->id, '请勿频繁提交！', 10);

        $usdtPrice = \app\admin\model\keerta\kline\Kline::where('id',1)
            ->cache(true, 60)
            ->value('price') ?? 7;
        $usdt = bcdiv($cny, $usdtPrice, 2);
        $order_sn = \fast\Random::uuid();
        Db::startTrans();
        try {
            $usdtType = $type == 'money' ? 'usdt' : 'withdraw_usdt';
            $memo = '人民币兑换USDT';
            MoneyLog::money($user->id, -$cny, 2, $type, $order_sn,$memo);
            MoneyLog::money($user->id, $usdt, 2, $usdtType, $order_sn,$memo);

            $model = new \app\admin\model\keerta\redeem\Redeem();
            $randId = new Recharge();
            $id = $randId->getOrderId($model);
            \app\admin\model\keerta\redeem\Redeem::create([
                'id' => $id,
                'user_id' => $user->id,
                'from' => $type,
                'to' => $usdtType,
                'hl' => $usdtPrice,
                'money' => $cny,
                'to_money' => $usdt,
                'order_sn' => $order_sn,
            ]);

            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('兑换成功！', [
            'order_sn' => $order_sn,
            'usdt' => $usdt,
            'cny' => $cny
        ]);

    }

    public function usdtToCny()
    {
        $param = ['type','usdt','pay_password'];
        $this->paramValidate($param);

        $type = $this->request->param('type', 'usdt', 'trim');
        if (!in_array($type, ['usdt', 'withdraw_usdt'])){
            $this->error('请选择正确的钱包类型！');
        }

        $usdt = $this->request->param('usdt','0', 'float');
        if ($usdt < 1) {
            $this->error('请输入正确的usdt金额');
        }
        $user = $this->auth->getUser();
        $pay_password = $this->request->param('pay_password', '', 'trim');
        if ($user->pay_password != (new Auth)->getEncryptPassword($pay_password, $user->salt)) {
            $this->error('支付密码错误！');
        }
        lock('user_usdtToCny_'.$user->id, '请勿频繁提交！',10);
        if ($user->$type < $usdt){
            $this->error('您的余额不足！');
        }
        $usdtPrice = \app\admin\model\keerta\kline\Kline::where('id',1)
            ->cache(true, 60)
            ->value('price') ?? 7;
        $cny = bcmul($usdt, $usdtPrice, 2);
        $order_sn = \fast\Random::uuid();
        Db::startTrans();
        try {
            $cnyType = $type == 'usdt' ? 'money' : 'withdraw_money';
            MoneyLog::money($user->id, -$usdt, 2, $type, $order_sn);
            MoneyLog::money($user->id, $cny, 2, $cnyType, $order_sn);

            $model = new \app\admin\model\keerta\redeem\Redeem();
            $randId = new Recharge();
            $id = $randId->getOrderId($model);

            \app\admin\model\keerta\redeem\Redeem::create([
                'id' => $id,
                'user_id' => $user->id,
                'from' => $type,
                'to' => $cnyType,
                'hl' => $usdtPrice,
                'money' => $usdt,
                'to_money' => $cny,
                'order_sn' => $order_sn,
            ]);

            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('兑换成功！', [
            'order_sn' => $order_sn,
            'usdt' => $usdt,
            'cny' => $cny
        ]);

    }

    /**
     * 可提现兑换充值
     *
     */
    public function redeem()
    {
        $param = ['type','money','pay_password'];
        $this->paramValidate($param);

        $type = $this->request->param('type', 'withdraw_money', 'trim');
        if (!in_array($type, ['withdraw_usdt', 'withdraw_money'])){
            $this->error('请选择正确的钱包类型！');
        }

        $money = $this->request->param('money','0', 'float');
        if ($money < 1) {
            $this->error('请输入正确的兑换金额，最低金额不能小于1！');
        }

        $user = $this->auth->getUser();
        $pay_password = $this->request->param('pay_password', '', 'trim');
        if ($user->pay_password != (new Auth)->getEncryptPassword($pay_password, $user->salt)) {
            $this->error('支付密码错误！');
        }
        lock('user_withdraw_to_recharge_'.$user->id, '请勿频繁操作！',10);
        if ($user->$type < $money){
            $this->error('您的余额不足！');
        }

        $order_sn = \fast\Random::uuid();
        Db::startTrans();
        try {
            if($type == 'withdraw_money'){
                $usdtType = 'money';
                $memo = '可提现余额兑换充值余额';

            }else{
                $usdtType = 'usdt';
                $memo = '可提现USDT兑换充值USDT';
            }
            MoneyLog::money($user->id, -$money, 2, $type, $order_sn,$memo);
            MoneyLog::money($user->id, $money, 2, $usdtType, $order_sn,$memo);

            $model = new \app\admin\model\keerta\redeem\Redeem();
            $randId = new Recharge();
            $id = $randId->getOrderId($model);
            \app\admin\model\keerta\redeem\Redeem::create([
                'id' => $id,
                'user_id' => $user->id,
                'from' => $type,
                'to' => $usdtType,
                'hl' => 1,
                'money' => $money,
                'to_money' => $money,
                'order_sn' => $order_sn,
            ]);

            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('兑换成功！', [
            'order_sn' => $order_sn,
            'money' => $money,
        ]);

    }
}