<?php

namespace app\api\controller\deerta;

use app\admin\model\keerta\withdraw\Cny;
use app\admin\model\keerta\withdraw\Setting;
use app\admin\model\keerta\withdraw\Usdt;
use app\common\controller\Api;
use app\common\library\Auth;
use app\common\model\MoneyLog;

class Withdraw extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 提现配置
     *
     */
    public function config()
    {
        $config = Setting::where('id', 1)->cache(true, 60)->find();
        $this->success('成功', $config);
    }
    /**
     * 提现
     *
     */
    public function index()
    {
        $userId = $this->auth->id;
        $user = \app\common\model\User::where('id', $userId)
            ->field('id,withdraw_money,withdraw_usdt')
            ->find();
        $cny_account = \app\admin\model\keerta\withdraw\Account::where('user_id', $userId)
            ->where('type', 1)
            ->field('id,bank_name,bank_card,bank_deposit')
            ->order('id desc')
            ->select();
        $usdt_account = \app\admin\model\keerta\withdraw\Account::where('user_id', $userId)
            ->where('type', 2)
            ->field('id,usdt_address')
            ->order('id desc')
            ->select();
        $this->success('success', [
            'user' => $user,
            'cny_account' => $cny_account,
            'usdt_account' => $usdt_account,
        ]);
    }

    /**
     * 余额提现
     *
     */
    public function cny()
    {
        $param = ['bank_card','bank_name','bank_deposit','money','pay_password','memo'];
        $this->paramValidate($param);

        $params = $this->request->post();
        // 规则验证
        try {
            $this->validateFailException()->validate($params,'WithdrawValidate.cny');

        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        $userId = $this->auth->id;

        $user = \app\common\model\User::where('id', $userId)->find();
        if($user['withdraw_money'] < $params['money']){
            $this->error('余额不足！');
        }

        $account = \app\admin\model\keerta\withdraw\Account::where('user_id', $userId)
            ->where('bank_card', $params['bank_card'])
            ->where('bank_name', $params['bank_name'])
            ->where('bank_deposit', $params['bank_deposit'])
            ->find();
        if(!$account){
            $this->error('未查询到有效的提现账户！');
        }

        $this->withdrawValidate($user, $params);

        $this->success('申请成功，请耐心等待审核！');
    }

    /**
     * usdt提现
     *
     */
    public function usdt()
    {
        $param = ['usdt_address','money','pay_password','memo'];
        $this->paramValidate($param);

        $params = $this->request->post();
        // 规则验证
        try {
            $this->validateFailException()->validate($params,'WithdrawValidate.usdt');
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        $userId = $this->auth->id;

        $user = \app\common\model\User::where('id', $userId)->find();

        if($user['withdraw_usdt'] < $params['money']){
            $this->error('余额不足！');
        }

        $account = \app\admin\model\keerta\withdraw\Account::where('user_id', $userId)
            ->where('usdt_address', $params['usdt_address'])
            ->find();
        if(!$account){
            $this->error('未查询到有效的提现账户！');
        }

        $this->withdrawValidate($user, $params, 'withdraw_usdt');

        $this->success('申请成功，请耐心等待审核！');
    }

    /**
     * 验证
     *
     */
    private function withdrawValidate($user, $params, $type = 'withdraw_money')
    {
        if($user['realname_status'] != 2){
            $this->error('请先完成实名认证！');
        }
        $realname = \app\admin\model\keerta\Realname::where('user_id', $user['id'])
            ->where('status', 1)
            ->find();
        if(!$realname){
            $this->error('请先完成实名认证！');
        }
        $pay_password = $this->request->param('pay_password', '', 'trim');
        if ($user->pay_password != (new Auth)->getEncryptPassword($pay_password, $user->salt)) {
            $this->error('支付密码错误！');
        }

        $config = Setting::where('id', 1)->cache(true, 60)->find();
        if ($config['switch'] == 0) {
            $this->error('提现功能已关闭！');
        }
        $hour = date('H');
        if ($hour < $config['start_time'] || $hour >= $config['end_time']) {
            $this->error('当前时间段不可提现！');
        }
        if ($params['money'] < $config['min']){
            $this->error('提现金额不能低于'.$config['min'].'元！');
        }
        if ($params['money'] > $config['max']){
            $this->error('提现金额不能高于'.$config['max'].'元！');
        }
        if ($params['money'] % $config['multiple'] != 0){
            $this->error('提现金额必须为'.$config['multiple'].'的倍数！');
        }
        lock('withdraw_'.$user->id, '请勿频繁提交', 10);

        if($type == 'withdraw_money'){
            $count = Cny::where('user_id', $user['id'])
                ->where('status','<', 2)
                ->whereTime('createtime', 'today')
                ->count();
        }else{
            $count = Usdt::where('user_id', $user['id'])
                ->where('status','<', 2)
                ->whereTime('createtime', 'today')
                ->count();
        }
        if ($count >= $config['num']){
            $this->error('今日提现次数已用完！');
        }

        $params['realname'] = $realname['surname'];
        $params['user_id'] = $user['id'];

        $params['status'] = 0;
        if ($config['fee'] > 0){
            $fee = $params['money'] * $config['fee'] / 100;
            $params['actual_money'] = $params['money'] - $fee;
        }else{
            $params['actual_money'] = $params['money'];
        }

        unset($params['pay_password']);
        $randId = new Recharge();

        if($type == 'withdraw_money'){
            $model = new Cny();
            $params['id'] = $randId->getOrderId($model);
            $params['order_sn'] = 'C' . date('mdHi') . chr(rand(65, 90)) . rand(1, 9) . chr(rand(65, 90)) . rand(100, 999);
            Cny::create($params);
        }else{
            $model = new Usdt();
            $params['id'] = $randId->getOrderId($model);
            $params['order_sn'] = 'U' . date('mdHi') . chr(rand(65, 90)) . rand(1, 9) . chr(rand(65, 90)) . rand(100, 999);
            Usdt::create($params);
        }

        MoneyLog::money($user['id'], -$params['money'], 4, $type, $params['order_sn']);
    }

}