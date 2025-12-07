<?php

namespace app\common\controller;

use app\common\library\Auth;
use think\Cache;
use think\Config;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Hook;
use think\Lang;
use think\Loader;
use think\Request;
use think\Response;
use think\Route;
use think\Validate;

/**
 * API控制器基类
 */
class Api
{

    /**
     * @var Request Request 实例
     */
    protected $request;

    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;

    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;

    /**
     * @var array 前置操作方法列表
     */
    protected $beforeActionList = [];

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 权限Auth
     * @var Auth
     */
    protected $auth = null;

    /**
     * 默认响应输出类型,支持json/xml
     * @var string
     */
    protected $responseType = 'json';

    /**
     * 接收的数据
     * @var array
     */
    protected $params = [];
    /**
     * 构造方法
     * @access public
     * @param Request $request Request 对象
     */
    public function __construct(Request $request = null)
    {
        $this->request = is_null($request) ? Request::instance() : $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                    $this->beforeAction($options) :
                    $this->beforeAction($method, $options);
            }
        }
    }

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Expose-Headers: __token__');//跨域让客户端获取到
        }
        //跨域请求检测
        check_cors_request();

        // 检测IP是否允许
        check_ip_allowed();

        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');

        $this->auth = Auth::instance();

        $modulename = $this->request->module();
        $controllername = Loader::parseName($this->request->controller());
        $actionname = strtolower($this->request->action());

        // token
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\Cookie::get('token')));

        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin)) {
            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin()) {
                $this->error(__('Please login first'), null, 401);
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    $this->error(__('You have no permission'), null, 403);
                }
            }
        } else {
            // 如果有传递token才验证是否登录状态
            if ($token) {
                $this->auth->init($token);
            }
        }

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);

        Config::set('upload', array_merge(Config::get('upload'), $upload));

        // 加载当前控制器语言包
        $this->loadlang($controllername);
    }
    /**
     * 参数校验
     *
     */
    public function paramValidate($arr = []): void
    {
        $this->request->filter(['strip_tags']);
        // 签名校验
        $this->params = $this->request->only($arr);
        $this->generateSign($this->params);
    }
    /**
     * 生成签名
     *
     * @param $params
     * @param $appSecret
     */
    protected function generateSign($params)
    {
        $appSecret = config('auth.app_secret');
        $timestamp = $this->request->header('timestamp', '');
        $nonce = $this->request->header('nonce', '');
        $sign = $this->request->header('sign', '');
        if (!$timestamp || !$nonce || !$sign){
            $this->error(__('签名参数错误'));
        }
        if (abs($timestamp - time()) > 6) {
            $this->error(__('签名超时') . abs($timestamp - time()));
        }
        // 防止重复请求
        if(Cache::has($sign)){
            $this->error(__('签名过期'));
        }

        // 签名参数
        $params = array_merge($params, [
            'timestamp' => $timestamp,
            'nonce'     => $nonce,
            'app_key' => config('auth.app_key'),
        ]);
        // 参数排序
        ksort($params);

        // 编码处理
        $stringToSign = http_build_query($params);
        // 生成签名
        $serverSign = strtoupper(hash_hmac('sha1', $stringToSign, $appSecret));
        // 签名比对
        if (!hash_equals($serverSign, $sign)) {
            write_log('ip：'. request()->ip() .'签名校验失败：' . $stringToSign.',sign：'.$serverSign, 'sign/failed');
            $this->error(__('签名校验失败'));
        }
        Cache::set($sign, 1, 10);
    }
    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        $name = Loader::parseName($name);
        $name = preg_match("/^([a-zA-Z0-9_\.\/]+)\$/i", $name) ? $name : 'index';
        $lang = $this->request->langset();
        $lang = preg_match("/^([a-zA-Z\-_]{2,10})\$/i", $lang) ? $lang : 'zh-cn';
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . $lang . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 操作成功返回的数据
     * @param string $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为1
     * @param string $type   输出类型
     * @param array  $header 发送的 Header 信息
     */
    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型
     * @param array  $header 发送的 Header 信息
     */
    protected function error($msg = '', $data = null, $code = 0, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($msg, $data = null, $code = 0, $type = null, array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => Request::instance()->server('REQUEST_TIME'),
            'data' => $data,
        ];
        // 如果未设置类型则使用默认类型判断
        $type = $type ? : $this->responseType;

        if (isset($header['statuscode'])) {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        } else {
            //未设置状态码,根据code值判断
            $code = $code >= 1000 || $code < 200 ? 200 : $code;
        }
        $response = Response::create($result, $type, $code)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 前置操作
     * @access protected
     * @param string $method  前置操作方法名
     * @param array  $options 调用参数 ['only'=>[...]] 或者 ['except'=>[...]]
     * @return void
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }

            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }

            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param array        $data     数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array        $message  提示信息
     * @param bool         $batch    是否批量验证
     * @param mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = Loader::validate();
            $v->rule($validate);
        } else {
            // 支持场景
            if (strpos($validate, '.')) {
                list($validate, $scene) = explode('.', $validate);
            }

            $v = Loader::validate($validate);

            !empty($scene) && $v->scene($scene);
        }

        // 批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }
        // 设置错误信息
        if (is_array($message)) {
            $v->message($message);
        }
        // 使用回调验证
        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }

            return $v->getError();
        }

        return true;
    }

    /**
     * 刷新Token
     */
    protected function token()
    {
        $token = $this->request->param('__token__');

        //验证Token
        if (!Validate::make()->check(['__token__' => $token], ['__token__' => 'require|token'])) {
            $this->error(__('Token verification error'), ['__token__' => $this->request->token()]);
        }

        //刷新Token
        $this->request->token();
    }
}
