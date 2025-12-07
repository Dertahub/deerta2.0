<?php

namespace app\api\controller;

use think\Db;
use think\Log;
use fast\Random;
use think\Config;
use think\Validate;
use EasyWeChat\Factory;
use app\admin\model\Admin;
use app\common\controller\Api;
use addons\kefu\library\Common;
use addons\kefu\library\Captcha;
use addons\kefu\library\WechatCrypto\WXBizMsgCrypt;

/**
 * KeFu 接口
 */
class Kefu extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['acceptWxMsg', 'goodsList', 'orderList', 'login', 'captcha', 'captchaPre'];// 实际使用中请去除`goodsList`和`orderList`
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    protected $wxBizMsg; // 消息加解密和辅助类实例

    /*
     * 获取未读消息数量
     */
    public function getUnreadMessagesCount()
    {
        $user = $this->auth->getUser();

        // 验证为客服用户
        $kefu_user_info = Common::checkKefuUser(false, $user->id);
        // 获取与客服的会话
        $kefu_session_id = Db::name('kefu_session')->where('user_id', $kefu_user_info['id'])->value('id');
        if ($kefu_session_id) {
            $unread_msg_count = Db::name('kefu_record')
                ->where('session_id', $kefu_session_id)
                ->where('sender_identity', 0)
                ->where('status', 0)
                ->count('id');
        } else {
            $unread_msg_count = 0;
        }

        $this->success('ok', $unread_msg_count);
    }

    /*
     * 获取最后一条未读消息
     */
    public function getUnreadMessages()
    {
        $user = $this->auth->getUser();

        $kefu_user_info = Common::checkKefuUser(false, $user->id);// 验证为客服用户
        $this->success('ok', Common::getUnreadMessages($kefu_user_info['user_id']));
    }

    /*
     * 演示用订单列表接口
     */
    public function orderList()
    {
        // $user = $this->auth->getUser();

        $logo = cdnurl('/assets/addons/kefu/img/buoy1.png', true);

        $order_list = [
            [
                'id'      => 1,
                'subject' => '接口演示订单标题-这是一个演示订单，我来自接口/api/KeFu/orderList',
                'logo'    => $logo,
                'note'    => '接口：/api/KeFu/orderList',
                'price'   => '99',
                'number'  => 1
            ],
            [
                'id'      => 2,
                'subject' => '小米9耳机正品type-c适用于8se/10半入耳式mix3 7pro note3/5原装',
                'logo'    => $logo,
                'note'    => '订单属性订单属性',
                'price'   => '101',
                'number'  => 2
            ],
            [
                'id'      => 3,
                'subject' => '小米9正品耳机等3件商品',
                'logo'    => $logo,
                'note'    => '颜色：红色；礼盒：不要礼盒',
                'price'   => '100',
                'number'  => 3
            ]
        ];

        $this->success('ok', $order_list);
    }

    /*
     * 演示用商品列表接口
     * 此接口用于返回客服前台可用的商品列表
     */
    public function goodsList()
    {
        // $user = $this->auth->getUser();

        $logo = cdnurl('/assets/addons/kefu/img/buoy1.png', true);

        $goods_list = [
            [
                'id'      => 1,
                'subject' => '接口演示商品名称-这是一个演示商品，我来自接口/api/KeFu/goodsList',
                'logo'    => $logo,
                'note'    => '接口：/api/KeFu/goodsList',
                'price'   => '99'
            ],
            [
                'id'      => 2,
                'subject' => '小米9耳机正品type-c适用于8se/10半入耳式mix3 7pro note3/5原装',
                'logo'    => $logo,
                'note'    => '小米通用',
                'price'   => '101'
            ],
            [
                'id'      => 3,
                'subject' => '小米9耳机正品type-c适用于8se/10半入耳式mix3 7pro note3/5原装',
                'logo'    => $logo,
                'note'    => '小米通用',
                'price'   => '100'
            ]
        ];

        $this->success('ok', $goods_list);
    }

    /*
     * 接受/处理来自微信的消息
     */
    public function acceptWxMsg()
    {
        $echostr = $this->request->get('echostr');
        $data    = $this->request->only(['msg_signature', 'timestamp', 'nonce', 'Encrypt']);

        if ($echostr) {
            if ($this->checkSignature()) {
                echo $echostr;
                return;
            }
        }

        // 获取微信小程序配置
        $wechat_temp = Db::name('kefu_config')
            ->whereIn('name', 'wechat_app_id,wechat_app_secret,wechat_token,wechat_encodingkey')
            ->select();
        foreach ($wechat_temp as $key => $value) {
            $wechat_config[$value['name']] = $value['value'];
        }

        $config  = [
            'app_id'  => $wechat_config['wechat_app_id'],
            'secret'  => $wechat_config['wechat_app_secret'],
            'token'   => $wechat_config['wechat_token'],
            'aes_key' => $wechat_config['wechat_encodingkey'],
            /*'log'     => [
                'level' => 'debug',
                'file'  => RUNTIME_PATH . 'log/kefu_wechat.log',
            ],*/
        ];
        $app     = Factory::miniProgram($config);
        $service = $app->customer_service;
        $msg     = '';

        $this->wxBizMsg = new WXBizMsgCrypt();
        $errCode        = $this->wxBizMsg->decryptMsg($data['msg_signature'], $data['timestamp'], $data['nonce'], $data['Encrypt'], $msg);

        if ($errCode == 0) {
            $msg = json_decode($msg, true);

            if (!$msg) {
                Log::record('微信客服消息解析出错，消息内容:' . $msg, 'notice');
                echo "success";
                return;
            }

            if (!empty($msg['MsgType']) && in_array($msg['MsgType'], ["text", "image"])) {

                if ($msg['MsgType'] == "image") {

                    $dlImg        = $this->wxBizMsg->saveImg($msg['MediaId']); // 保存图片
                    $content      = request()->domain() . $dlImg;
                    $message_type = 1;
                } else {

                    $content      = $msg['Content'];
                    $message_type = 0;
                }

                $session = $this->wxBizMsg->userInitialize($msg['FromUserName']);

                if ($session['code'] == 1 || $session['code'] == 2) {

                    if (Db::name('kefu_blacklist')->where('user_id', $session['kefu_user']['id'])->value('id')) {
                        $this->wxBizMsg->sendMessage('您的消息被拒收了，请注意您的发言~', $msg['FromUserName']);
                        return;
                    }

                    if ($session['session']) {
                        // 通知客服新消息
                        Common::socketMessage($session['session']['id'], $content, $message_type, $session['session']['user_id'] . '||user');
                    } else {

                        $user_info = Common::userInfo($session['kefu_user']['id'] . '||user');

                        $last_leave_message_time = Db::name('kefu_leave_message')
                            ->where('user_id', $user_info['id'])
                            ->order('createtime desc')
                            ->value('createtime');

                        if ($last_leave_message_time && ($last_leave_message_time + 20) > time()) {
                            $this->wxBizMsg->sendMessage('由于当前无客服代表在线，请不要频繁发送消息，感谢您的支持！', $msg['FromUserName']);
                            echo "success";
                            return;
                        }

                        $leave_message = [
                            'user_id'    => $user_info['id'],
                            'name'       => $user_info['nickname'],
                            'message'    => $content,
                            'createtime' => time(),
                        ];

                        if (Db::name('kefu_leave_message')->insert($leave_message)) {

                            $leave_message_id = Db::name('kefu_leave_message')->getLastInsID();

                            // 记录轨迹
                            $trajectory = [
                                'user_id'    => $user_info['id'],
                                'csr_id'     => 0,
                                'log_type'   => 6,
                                'note'       => $leave_message_id,
                                'url'        => '',
                                'referrer'   => '',
                                'createtime' => time(),
                            ];

                            Db::name('kefu_trajectory')->insert($trajectory);
                            $this->wxBizMsg->sendMessage('留言成功！', $msg['FromUserName']);
                        }
                    }

                } elseif ($session['code'] == 0) {
                    $this->wxBizMsg->sendMessage($session['msg'], $msg['FromUserName']);
                }

            } else {

                $session = $this->wxBizMsg->userInitialize($msg['FromUserName']);

                if ($session['code'] == 1 || $session['code'] == 0) {
                    $this->wxBizMsg->sendMessage($session['msg'], $msg['FromUserName']);
                }
            }
        } else {
            Log::record('微信客服消息解析出错，消息内容 errCode:' . $errCode, 'notice');
        }

        echo "success";
    }

    /*是否是验证消息*/
    private function checkSignature()
    {
        $wechat_token = Db::name('kefu_config')->where('name', 'wechat_token')->value('value');

        $signature = $this->request->get('signature');
        $timestamp = $this->request->get('timestamp');
        $nonce     = $this->request->get('nonce');

        $tmpArr = [$wechat_token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public function login()
    {
        $username  = $this->request->post('username');
        $password  = $this->request->post('password');
        $captcha   = $this->request->post('captcha');
        $captchaId = $this->request->post('captcha_id');

        $rule = [
            'captcha'    => 'require|length:4,6',
            'username'   => 'require|length:3,30',
            'password'   => 'require|length:6,30',
            'captcha_id' => 'require'
        ];
        $msg  = [
            'captcha.require'    => '请输入验证码',
            'captcha.length'     => '请输入正确的验证码~',
            'username.require'   => '用户名不能为空',
            'username.length'    => '用户名必须为3到30个字符',
            'password.require'   => '密码不能为空',
            'password.length'    => '密码必须为3到30个字符',
            'captcha_id.require' => '参数缺失！'
        ];
        $data = [
            'username'   => $username,
            'password'   => $password,
            'captcha'    => $captcha,
            'captcha_id' => $captchaId
        ];

        $validate = new Validate($rule, $msg);
        $result   = $validate->check($data);
        if (!$result) {
            $this->error(__($validate->getError()));
        }

        $captchaObj = new Captcha();
        if (!$captchaObj->check($captcha, $captchaId)) {
            $this->error(__('请输入正确的验证码~'));
        }

        // 检查为管理员
        $userInfo = $this->checkAdminByPassword($username, $password);
        if (!$userInfo) {
            $this->error('账号或密码错误，请重试！');
        }
        $userInfo = Common::checkAdmin(false, $userInfo->id);
        if ($userInfo) {
            $keeptime   = 864000;
            $expiretime = time() + $keeptime;

            // 原规则为单纯的id，若需修改附加的字符串，请将`Common::checkAdmin`方法里边的附加字符串一起修改
            $sign = $userInfo['id'] . 'kefu_admin_sign_additional';

            $key               = md5(md5($sign) . md5($keeptime) . md5($expiretime) . $userInfo['token']);
            $tokenData         = [$userInfo['id'], $keeptime, $expiretime, $key];
            $userInfo['token'] = implode('|', $tokenData);
        } else {
            $this->error('登录失败，请确认用户是客服代表！');
        }

        $this->success('ok', [
            'userinfo' => $userInfo
        ]);
    }

    public function captchaPre()
    {
        $captchaId = md5(Random::uuid());
        $this->success('', [
            'captcha_id' => $captchaId
        ]);
    }

    public function captcha()
    {
        $captchaId = $this->request->request('captcha_id');
        $config    = array(
            'codeSet'  => '123456789',            // 验证码字符集合
            'fontSize' => 22,                     // 验证码字体大小(px)
            'useCurve' => false,                  // 是否画混淆曲线
            'useNoise' => true,                   // 是否添加杂点
            'length'   => 4,                      // 验证码位数
            'bg'       => array(255, 255, 255),   // 背景颜色
        );
        $captcha   = new Captcha($config);
        return $captcha->entry($captchaId);
    }

    private function checkAdminByPassword($username, $password)
    {
        $admin = Admin::get(['username' => $username]);
        if (!$admin) {
            return false;
        }
        if ($admin['status'] == 'hidden') {
            return false;
        }
        if (Config::get('fastadmin.login_failure_retry') && $admin->loginfailure >= 10 && time() - $admin->updatetime < 86400) {
            return false;
        }
        if ($admin->password != md5(md5($password) . $admin->salt)) {
            $admin->loginfailure++;
            $admin->save();
            return false;
        }

        // 不重置管理员token
        $admin->loginfailure = 0;
        $admin->logintime    = time();
        $admin->loginip      = request()->ip();
        if (!$admin->token) {
            $admin->token = Random::uuid();
        }
        $admin->save();
        return $admin;
    }
}
