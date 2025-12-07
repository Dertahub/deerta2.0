<?php

namespace app\api\controller\xy;

use app\admin\model\keerta\Level;
use app\common\controller\Api;
use app\common\service\IdcardService;
use Exception;
use think\Config;
use think\exception\ValidateException;
use think\Validate;
use app\admin\model\keerta\order\Interest;

class User extends Api
{
    protected $noNeedLogin = ['mobileRegister','emailRegister','mobileLogin','emailLogin','changePassword','forgetPassword','mobileIsRegister'];
    protected $noNeedRight = ['*'];

    /**
     * 个人信息
     *
     */
    public function userinfo()
    {
        $this->success('success', $this->info());
    }
    public function info()
    {
        $userinfo['userinfo'] = $this->auth->getUserinfo();
        $userinfo['level_info'] = Level::where('id', $userinfo['userinfo']['level_id'])
            ->field('id,level_name,level_logo')
            ->find();
        if (empty($userinfo['level_info'])){
            $this->error('等级信息不存在'.$userinfo['userinfo']['level_id']);
        }
        if($userinfo['userinfo']['realname_status'] == 2){
            $userinfo['userinfo']['realname'] = \app\admin\model\keerta\Realname::where('user_id', $this->auth->id)->value('surname') ?? '';
        }else{
            $userinfo['userinfo']['realname'] = '';
        }

        $userinfo['level_info']['level_logo'] = $userinfo['level_info']['level_logo'] ? cdnurl($userinfo['level_info']['level_logo'], true) : '';
        $userinfo['chicang'] = Interest::where('type',4)
            ->where('user_id', $userinfo['userinfo']['id'])
            ->where('is_bouns',0)
            ->sum('amount');
        $userinfo['bj_amount'] = \app\admin\model\keerta\idle\Order::where('user_id',$userinfo['userinfo']['id'])
            ->where('status',1)
            ->cache(true, 6)
            ->sum('amount');
        return $userinfo;
    }
    /**
     * 注册
     */
    public function mobileRegister()
    {
        lock('mobileRegister');
        $param = ['mobile','password','confirm_password','pay_password','confirm_pay_password','invite_code','device_id','country_code'];
        $this->paramValidate($param);

        // 规则验证
        try {
            $this->validateFailException()->validate($this->params,'UserValidate.mobileRegister');

            if($this->params['country_code'] == '86' || $this->params['country_code'] == '+86'){
                if (!Validate::regex($this->params['mobile'], "^1\d{10}$")) {
                    $this->error(__('Mobile is incorrect'));
                }
            }

            $this->registerValidate();
        }catch (Exception|ValidateException $e){
            unlock('mobileRegister');
            $this->error($e->getMessage());
        }

        $ret = $this->auth->register(
            $this->params['password'],
            $this->params['pay_password'],
            '',
            $this->params['mobile'],
            [
                'device_id' => $this->params['device_id'],
                'country_code' => $this->params['country_code'],
                'refer' => $this->params['refer'],
                'refer_path' => $this->params['refer_path'],
            ]
        );

        if ($ret) {
            $this->success(__('Sign up successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 邮箱注册
     *
     */
    public function emailRegister()
    {
        lock('emailRegister');
        $param = ['email','password','pay_password','confirm_password','confirm_pay_password','invite_code','device_id'];
        $this->paramValidate($param);

        // 规则验证
        try {
            $this->validateFailException()->validate($this->params,'UserValidate.emailRegister');

            $this->registerValidate();

            // 对邮箱类型做一个限制，只允许qq,163,gmail的邮箱进行注册
            $email_type = $this->getEmailType($this->params['email']);
            if (!in_array($email_type, ['qq', '163', 'gmail'])){
                throw new Exception('只支持QQ、163和Gmail邮箱注册');
            }
        }catch (Exception|ValidateException $e){
            unlock('emailRegister');
            $this->error($e->getMessage());
        }

        $ret = $this->auth->register(
            $this->params['password'],
            $this->params['pay_password'],
            $this->params['email'],
            '',
            [
                'device_id' => $this->params['device_id'],
                'refer' => $this->params['refer'],
                'refer_path' => $this->params['refer_path'],
            ]
        );

        if ($ret) {
            $this->success(__('Sign up successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }
    /**
     * 获取邮箱类型
     * @param string $email 邮箱地址
     * @return string 邮箱类型
     */
    private function getEmailType($email)
    {
        // 提取邮箱域名部分
        $domain = substr(strrchr($email, "@"), 1);
        $domain = strtolower($domain);
        // 判断邮箱类型
        if (in_array($domain, ['qq.com','foxmail.com'])) {
            return 'qq';
        } elseif (in_array($domain, ['163.com','126.com','yeah.net'])) {
            return '163';
        } elseif (in_array($domain, ['gmail.com','googlemail.com','sina.com'])) {
            return 'gmail';
        }

        // 如果不是支持的邮箱类型，返回主域名
        $domainParts = explode('.', $domain);
        $count = count($domainParts);
        if ($count >= 2) {
            return $domainParts[$count - 2] . '.' . $domainParts[$count - 1];
        }

        return $domain;
    }

    /**
     * 注册的验证逻辑
     *
     * @throws Exception
     */
    public function registerValidate()
    {
        $ip = request()->ip();
        $user = \app\common\model\User::where('joinip', $ip)
            ->whereTime('createtime', 'today')
            ->count();
        if($user >= 3){
            throw new Exception('当前ip今日注册次数过多');
        }

        // 设备唯一标识验证
        $device = \app\common\model\User::where('device_id', $this->params['device_id'])
            ->whereTime('createtime', 'today')
            ->count();
        if($device >= 3){
            throw new Exception('当前设备今日注册次数过多');
        }

        if($this->params['password'] != $this->params['confirm_password']){
            throw new Exception('两次输入的密码不一致');
        }
        // 邀请码验证
        $invite = \app\common\model\User::where('invite_code', $this->params['invite_code'])
            ->cache( true, 60)
            ->find();
        if(!$invite){
            throw new Exception('邀请码不存在');
        }

        // 邀请码下近一小时的注册数
        $invite_count = \app\common\model\User::where('refer', $invite['id'])
            ->whereTime('createtime', '-10 minute')
            ->count();
        if($invite_count >= 10){
            throw new Exception('当前邀请码限制使用');
        }
        $this->params['refer'] = $invite['id'];
        $this->params['refer_path'] = $invite['refer_path'];
    }

    /**
     * 手机号登录
     *
     */
    public function mobileLogin()
    {
        $param = ['mobile','password'];
        $this->paramValidate($param);

        lock('mobileLogin' . $this->params['mobile']);
        // 规则验证
        try {
            $this->validateFailException()->validate($this->params,'UserValidate.mobileLogin');

        }catch (Exception|ValidateException $e){
            $this->error($e->getMessage());
        }

        $ret = $this->auth->login($this->params['mobile'], $this->params['password']);
        if ($ret) {
            $this->success(__('Logged in successful'), $this->info());
        } else {
            $this->error($this->auth->getError());
        }
    }
    /**
     * 邮箱登录
     *
     */
    public function emailLogin()
    {
        $param = ['email','password'];
        $this->paramValidate($param);

        lock('emailLogin' . $this->params['email']);
        // 规则验证
        try {
            $this->validateFailException()->validate($this->params,'UserValidate.emailLogin');

        }catch (Exception|ValidateException $e){
            $this->error($e->getMessage());
        }

        $ret = $this->auth->login($this->params['email'], $this->params['password']);
        if ($ret) {
            $this->success(__('Logged in successful'), $this->info());
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 修改密码
     *
     */
    public function changePassword()
    {
        $params = $this->request->post();
        $param = ['type','old_password','new_password','confirm_password','surname','idcard'];
        $this->paramValidate($param);

        // 规则验证
        try {
            $this->validateFailException()->validate($this->params,'UserValidate.changePassword');

            IdcardService::validateIdCard($params['idcard']);
        }catch (Exception|ValidateException $e){
            $this->error($e->getMessage());
        }

        if($params['old_password'] == $params['new_password']){
            $this->error('新密码不能与旧密码相同');
        }
        if ($params['new_password'] != $params['confirm_password']){
            $this->error('两次输入的密码不一致');
        }

        lock('changePassword' . $params['idcard']);

        $realName = \app\admin\model\keerta\Realname::where('idcard', $params['idcard'])
            ->where('status',1)
            ->find();
        if (!$realName){
            $this->error('未查询到有效的身份信息');
        }
        if($realName['surname'] != $params['surname']){
            $this->error('请输入正确的实名信息');
        }
        $user = \app\common\model\User::where('id', $realName['user_id'])
            ->find();
        if (!$user){
            $this->error('用户不存在');
        }
        if($user['status'] != 'normal'){
            $this->error('用户状态异常');
        }
        //模拟一次登录
        $this->auth->direct($user->id);


        if($params['type'] == 'password'){
            $ret = $this->auth->changepwd($params['new_password'], $params['old_password']);
        }else{
            $ret = $this->auth->changepaypwd($params['new_password'], $params['old_password']);
        }
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }
    public function forgetPassword()
    {
        $params = $this->request->post();
        $param = ['type','new_password','confirm_password','surname','idcard'];
        $this->paramValidate($param);

        // 规则验证
        try {
            $this->validateFailException()->validate($this->params,'UserValidate.forgetPassword');

            IdcardService::validateIdCard($params['idcard']);
        }catch (Exception|ValidateException $e){
            $this->error($e->getMessage());
        }

        if ($params['new_password'] != $params['confirm_password']){
            $this->error('两次输入的密码不一致');
        }

        lock('forgetPassword' . $params['idcard']);

        $realName = \app\admin\model\keerta\Realname::where('idcard', $params['idcard'])
            ->where('status',1)
            ->find();
        if (!$realName){
            $this->error('未查询到有效的身份信息');
        }
        if($realName['surname'] != $params['surname']){
            $this->error('请输入正确的实名信息');
        }
        $user = \app\common\model\User::where('id', $realName['user_id'])
            ->find();
        if (!$user){
            $this->error('用户不存在');
        }
        if($user['status'] != 'normal'){
            $this->error('用户状态异常');
        }
        //模拟一次登录
        $this->auth->direct($user->id);


        if($params['type'] == 'password'){
            $ret = $this->auth->changepwd($params['new_password'],'', true);
        }else{
            $ret = $this->auth->changepaypwd($params['new_password'],'', true);
        }
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 判断手机号是否已注册
     *
     */
    public function mobileIsRegister()
    {
        $param = ['account','type'];
        $this->paramValidate($param);

        $account = $this->params['account'];
        $type = $this->params['type'];
        $ret = \app\common\model\User::where($type, $account)
            ->find();
        if ($ret){
            $this->success('已注册');
        }else{
            $this->error('未注册');
        }
    }
}