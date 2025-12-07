<?php

namespace addons\kefu\controller;

use think\Db;
use think\Hook;
use think\Lang;
use think\Cookie;
use app\admin\library\Auth;
use app\common\model\Config;
use app\common\library\Upload;
use addons\kefu\library\Common;
use app\common\exception\UploadException;

class Index extends Base
{
    protected $noNeedLogin = ['initialize', 'loadMessagePrompt', 'upload', 'mobile', 'index'];

    protected $chat_config;

    protected $token_info = false;// 用户、游客、管理员的资料

    protected $token_list = [];// 要发送给前台的token（前台利用这些token链接websocket）

    protected $referrer = '';

    public function index()
    {
        $this->view->assign('chat_name', $this->chat_config['chat_name']);
        return $this->view->fetch();
    }

    public function mobile()
    {
        $this->view->assign('toolbar', $this->chat_config['toolbar']);// 工具栏配置
        $this->view->assign('chat_name', $this->chat_config['chat_name']);
        return $this->view->fetch();
    }

    /**
     * 获取配置、初始化AJAX请求过来的用户的身份等
     */
    public function _initialize()
    {
        parent::_initialize();

        //跨域请求检测
        check_cors_request();

        $this->referrer = Common::trajectoryAnalysis($this->request->get('referrer'));

        // 读取工具栏
        $toolbar_temp = Db::name('kefu_toolbar')->where('status', 1)->where('deletetime', null)->select();
        // 以mark为键
        foreach ($toolbar_temp as $value) {
            $value['icon_image']                = Common::imgSrcFill($value['icon_image']);
            $toolbar['toolbar'][$value['mark']] = $value;
        }

        $this->chat_config      = Db::name('kefu_config')->column('name,value');
        $kefu_config            = get_addon_config('kefu');
        $kefu_config['__CDN__'] = config('view_replace_str.__CDN__');
        $kefu_config['__CDN__'] = $kefu_config['__CDN__'] ?: $this->request->domain();

        // 上传配置
        $upload['upload'] = Config::upload();
        // 上传信息配置后
        Hook::listen("upload_config_init", $upload['upload']);
        if ($upload['upload']['storage'] != 'local') {
            $upload['upload']['cdnurl']    = $upload['upload']['cdnurl'] ?: cdnurl('', true);
            $upload['upload']['uploadurl'] = preg_match('/^http(s)?:\/\//', $upload['upload']['uploadurl']) ? $upload['upload']['uploadurl'] : $this->request->domain() . $upload['upload']['uploadurl'];
        } else {
            $upload['upload']['cdnurl']    = $this->request->domain();
            $upload['upload']['uploadurl'] = addon_url('kefu/index/upload', [], '', true);
        }
        $this->chat_config = array_merge($this->chat_config, $kefu_config, $upload, $toolbar);
        unset($toolbar_temp);
        unset($toolbar);

        // 页面排除
        if ($this->chat_config['rule_out_url'] && isset($_SERVER['HTTP_REFERER'])) {

            $http_referer = $this->urlDealWith($_SERVER['HTTP_REFERER']);
            $rule_out_url = explode(PHP_EOL, $this->chat_config['rule_out_url']);

            foreach ($rule_out_url as $value) {
                if ($this->urlDealWith($value) == $http_referer) {
                    $this->result(null, 401, $this->chat_config['chat_name'] . ' 当前页面被设置为不启动', 'json');
                }
            }
        }

        // 用户登录
        $data                                    = $this->request->only(['modulename', 'token', 'kefu_tourists_token']);
        $this->token_list['kefu_tourists_token'] = Cookie::get('kefu_user');
        if (!$this->token_list['kefu_tourists_token'] && isset($data['kefu_tourists_token'])) {
            $this->token_list['kefu_tourists_token'] = $data['kefu_tourists_token'];
        }

        if (!isset($data['modulename'])) {
            if ($this->request->action() == 'mobile' || $this->request->action() == 'index') {
                $data['modulename'] = 'index';
            } else {
                $this->result(null, 0, $this->chat_config['chat_name'] . ' 模块未知' . $this->request->action(), 'json');
            }
        }

        if ($data['modulename'] == 'admin') {

            // 验证管理员身份
            $auth = Auth::instance();
            if ($auth->isLogin()) {
                $this->token_info = Common::checkAdmin(false, $auth->id);
            } elseif ($data['token']) {
                $this->token_info = Common::checkAdmin($data['token']);
            }

            if ($this->token_info) {
                // workerman 中不支持 PHP session和cookie，所以 $auth 类失效
                // 此处对管理员 token 加密稍作修改，供客服自动登录使用
                $keeptime   = 864000;
                $expiretime = time() + $keeptime;

                // 原规则为单纯的id，若需修改附加的字符串，请将`Common::checkAdmin`方法里边的附加字符串一起修改
                $sign = $this->token_info['id'] . 'kefu_admin_sign_additional';

                $key                            = md5(md5($sign) . md5($keeptime) . md5($expiretime) . $this->token_info['token']);
                $cookie_data                    = [$this->token_info['id'], $keeptime, $expiretime, $key];
                $this->token_list['kefu_token'] = implode('|', $cookie_data);
                unset($this->token_info['token']);
            }

            // 清理轨迹
            switch ($this->chat_config['trajectory_save_cycle']) {
                case 0:
                    $where_time = 604800; // 清理7天前的
                    break;
                case 1:
                    $where_time = 2592000; // 30天
                    break;
                case 2:
                    $where_time = 5184000; // 60天
                    break;

                default:
                    $where_time = false; // 不清理
                    break;
            }

            if ($where_time) {
                Db::name('kefu_trajectory')->where('createtime', '<', time() - $where_time)->delete();
            }

        } elseif ($data['modulename'] != 'admin') {
            // 验证用户身份
            $auth  = \app\common\library\Auth::instance();
            $token = Cookie::get('token');
            $token = (!$token && isset($data['token'])) ? $data['token'] : $token;
            if ($token) {
                $auth->init($token);
                if ($auth->isLogin()) {
                    $this->token_info = Common::checkKefuUser($this->token_list['kefu_tourists_token'], $auth->id);
                    if (!$this->token_info && !$this->token_list['kefu_tourists_token']) {
                        // 该用户未绑定游客，建立游客身份并绑定
                        $tourists = Common::createTourists($this->referrer . ' IP:' . $this->request->ip());
                        if ($tourists) {
                            $this->token_list['kefu_tourists_token'] = $tourists['kefu_user_cookie'];
                            Cookie::set('kefu_user', $tourists['kefu_user_cookie'], 315360000);
                        } else {
                            $this->result(null, 401, $this->chat_config['chat_name'] . ' 游客创建失败！', 'json');
                        }

                        $this->token_info = Common::checkKefuUser($this->token_list['kefu_tourists_token'], $auth->id);
                    }

                    // workerman 中不支持 PHP session和cookie，所以 $auth 类失效
                    // 在开启 $cookie_httponly 时，对用户的 token 加密稍作修改，供客服自动登录使用
                    if ($this->token_info) {
                        $cookie_httponly = config('cookie.httponly');
                        if (!$cookie_httponly) {
                            $this->token_list['kefu_token'] = $token;
                        } else {

                            // 若需修改附加的字符串，请将`Events::onWebSocketConnect`方法里边的附加字符串一起修改
                            // 先用 user_token 数据表中的token字段同样的加密算法对token进行加密，否则workerman无法识别用户身份
                            $sign                           = Common::getEncryptedToken($token) . 'kefu_user_sign_additional';
                            $key                            = md5(md5($auth->id) . md5($sign));
                            $cookie_data                    = [$auth->id, $key];
                            $this->token_list['kefu_token'] = implode('|', $cookie_data);
                        }
                    }
                }
            }

            if (!$this->token_info && $this->token_list['kefu_tourists_token']) {
                // 验证游客用户身份
                $this->token_info = Common::checkKefuUser($this->token_list['kefu_tourists_token'], 0);
            }
        }

        if (!$this->token_info && $data['modulename'] != 'admin') {
            $tourists = Common::createTourists($this->referrer . ' IP:' . $this->request->ip());
            if ($tourists) {
                $this->token_list['kefu_tourists_token'] = $tourists['kefu_user_cookie'];
                Cookie::set('kefu_user', $tourists['kefu_user_cookie'], 315360000);
                $this->token_info = Common::checkKefuUser($this->token_list['kefu_tourists_token'], 0);
            } else {
                $this->result(null, 401, $this->chat_config['chat_name'] . ' 游客创建失败！', 'json');
            }
        }

        $this->view->assign('cdnurl', $kefu_config['__CDN__']);
    }

    public function initialize()
    {
        $res_data    = [];
        $data        = $this->request->only(['modulename']);
        $current_url = $this->request->header('referer');

        if ($this->token_info) {
            if (isset($this->token_info['blacklist']) && $this->token_info['blacklist']) {
                $this->result(null, 401, '!', 'json');// 黑名单用户
            }
        } else {
            $this->result(null, 401, $this->chat_config['chat_name'] . ' 无法识别用户！', 'json');
        }

        $this->view->assign('toolbar', $this->chat_config['toolbar']);// 工具栏配置

        if ($data['modulename'] == 'admin') {

            if (isset($this->chat_config['toolbar']['fastreply'])) {
                // 快捷回复
                $fast_reply = Db::name('kefu_fast_reply')
                    ->where('admin_id=' . $this->token_info['id'] . ' OR admin_id=0')
                    ->where('status', '1')
                    ->where('deletetime', null)
                    ->select();

                $fast_reply_temp = [];
                foreach ($fast_reply as $value) {
                    $fast_reply_temp[$value['id']] = $value;
                }
                unset($fast_reply);
                $res_data['fast_reply'] = $fast_reply_temp;

                $this->view->assign('fast_reply', $fast_reply_temp);
            }

            $res_data['window_html'] = $this->view->fetch(ROOT_PATH . 'addons/kefu/view/' . trim($this->chat_config['theme']) . '/modaltpl/admin.html');
        } else {

            $this->chat_config['invite_box_img']         = $this->chat_config['invite_box_img'] ? Common::imgSrcFill($this->chat_config['invite_box_img'], false) : false;
            $this->chat_config['auto_invitation_switch'] = ($this->chat_config['invite_box_img']) ? $this->chat_config['auto_invitation_switch'] : 0;

            // 只在有客服在线时弹出邀请框
            if ($this->chat_config['only_csr_online_invitation'] && $this->chat_config['auto_invitation_switch']) {
                $online_csr                                  = Db::name('kefu_csr_config')
                    ->where('status', 3)
                    ->value('admin_id');
                $this->chat_config['auto_invitation_switch'] = $online_csr ? $this->chat_config['auto_invitation_switch'] : 0;
            }

            // 前台轮播图
            $this->chat_config['slider_images'] = $this->chat_config['slider_images'] ? explode(',', trim($this->chat_config['slider_images'], ',')) : [];
            foreach ($this->chat_config['slider_images'] as $key => $value) {
                $this->chat_config['slider_images'][$key] = Common::imgSrcFill($value, false);
            }

            $res_data['window_html'] = $this->view->fetch(ROOT_PATH . 'addons/kefu/view/' . trim($this->chat_config['theme']) . '/modaltpl/index.html');
        }

        // 记录轨迹
        if (isset($this->token_info['trajectory'])) {
            // 用户轨迹
            $trajectory = [
                'user_id'    => $this->token_info['id'],
                'csr_id'     => $this->token_info['trajectory']['csr_id'],
                'log_type'   => 0,
                'note'       => $this->token_info['trajectory']['note'],
                'url'        => $current_url,
                'referrer'   => $this->referrer,
                'createtime' => time(),
            ];

            Db::name('kefu_trajectory')->insert($trajectory);
        }

        // 窗口抖动配置处理
        $this->chat_config['is_shake'] = false;
        if ($this->chat_config['new_message_shake'] == 3) {
            $this->chat_config['is_shake'] = true;
        } elseif ($this->chat_config['new_message_shake'] == 1 && $data['modulename'] != 'admin') {
            $this->chat_config['is_shake'] = true;
        } elseif ($this->chat_config['new_message_shake'] == 2 && $data['modulename'] == 'admin') {
            $this->chat_config['is_shake'] = true;
        }

        // 配置排除
        $except_config = [
            'wechat_app_id',
            'wechat_app_secret',
            'wechat_encodingkey',
            'wechat_token',
            'worker_process_number',
            'csr_admin',
            'csr_distribution',
            'register_port',
            'gateway_process_number',
            'internal_start_port',
            'kbs_switch',
            'trajectory_save_cycle',
            'ssl_cert',
            'ssl_cert_key'
        ];
        foreach ($except_config as $value) {
            if (in_array($value, $except_config)) {
                unset($this->chat_config[$value]);
            }
        }

        // 防止商品和订单卡片暴露后台地址
        if ($this->token_info['source'] != 'csr') {
            unset($this->chat_config['toolbar']['goods']['card_url']);
            unset($this->chat_config['toolbar']['order']['card_url']);
        }

        $res_data['user_info']           = $this->token_info;
        $res_data['new_msg']             = Common::getUnreadMessages($this->token_info['user_id']);
        $this->chat_config['modulename'] = $data['modulename'];
        $res_data['config']              = $this->chat_config;
        $res_data['token_list']          = $this->token_list;
        $this->result($res_data, 1, 'ok', 'json');
    }

    /**
     * 去除URL中的 index.php、https://、http://、去除参数
     * @param  [type] $url [description]
     * @return string      处理结果
     */
    private function urlDealWith($url)
    {
        $url = explode('?', $url);
        $url = $url[0] ?? ''; // 只要 ? 号前的字符串
        return str_replace(['http://', 'https://'], '', trim($url));
    }

    /**
     * 供跨站下载来信提示音文件（未使用云存储）
     */
    public function loadMessagePrompt()
    {
        $file = ROOT_PATH . 'public' . $this->chat_config['ringing'];
        header("Content-type:application/octet-stream");
        $filename = basename($file);
        header("Content-Disposition:attachment;filename = " . $filename);
        header("Accept-ranges:bytes");
        header("Accept-length:" . filesize($file));
        readfile($file);
    }

    public function upload()
    {
        // 载入语言包，避免出现英文错误提示
        Lang::load(APP_PATH . 'api/lang/zh-cn.php');

        // 实现普通上传文件即可，安装了云存储的系统前台将自动使用对应云存储的URL进行上传
        $file = $this->request->file('file');
        try {
            $upload     = new Upload($file);
            $attachment = $upload->upload();
        } catch (UploadException $e) {
            $this->error($e->getMessage());
        }
        $this->result(['url' => $attachment->url], 1, null, 'json');
    }
}
