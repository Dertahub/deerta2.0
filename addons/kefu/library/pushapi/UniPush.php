<?php

namespace addons\kefu\library\pushapi;

use think\Db;
use addons\kefu\library\pushapi\GTClient;
use addons\kefu\library\pushapi\request\push\GTPushRequest;
use addons\kefu\library\pushapi\request\push\GTSettings;
use addons\kefu\library\pushapi\request\push\GTPushMessage;
use addons\kefu\library\pushapi\request\push\GTNotification;
use addons\kefu\library\pushapi\request\push\GTPushChannel;
use addons\kefu\library\pushapi\request\push\android\GTThirdNotification;
use addons\kefu\library\pushapi\request\push\android\GTAndroid;
use addons\kefu\library\pushapi\request\push\android\GTUps;
use addons\kefu\library\pushapi\request\push\GTStrategy;
use addons\kefu\library\pushapi\request\push\ios\GTIos;
use addons\kefu\library\pushapi\request\push\ios\GTAps;
use addons\kefu\library\pushapi\request\push\ios\GTAlert;

class UniPush
{
    protected static $instance = null;

    protected $config = [];

    protected $apiUrl = 'https://restapi.getui.com';

    /**
     * GTClient 类实例
     * @var null
     */
    protected $api = null;

    protected $_error = '';

    public function __construct()
    {
        $pushConfigDb = Db::name('kefu_config')->field('name,value')->whereIn('name', 'uni_push_appid,uni_push_appkey,uni_push_master_secret,package_name_android')->select();
        foreach ($pushConfigDb as $key => $value) {
            $this->config[$value['name']] = $value['value'];
        }

        $this->api = new GTClient($this->apiUrl, $this->config['uni_push_appkey'], $this->config['uni_push_appid'], $this->config['uni_push_master_secret']);
    }

    /**
     *
     * @return UniPush
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 单推
     * @param string $cid clientid
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param string $payload payload
     * @param string $platform 用户系统平台
     * @return bool
     */
    public function single($cid, $title, $content, $payload, $platform = 'android')
    {
        $push = $this->paramPack($title, $content, $payload, $platform);
        $push->setCid($cid);

        /*print_r($push->getApiParam());
        return ;*/

        $this->api->pushApi()->pushToSingleByCid($push);
    }

    /**
     * 参数打包
     * @return GTPushRequest 类实例
     */
    public function paramPack($title, $content, $payload, $platform)
    {
        $title   = mb_substr($title, 0, 20); // VIVO手机限制20个汉字
        $content = mb_substr($content, 0, 50); // VIVO手机限制50个汉字

        $push = new GTPushRequest();
        $push->setRequestId($this->microTime());

        /**
         * 设置推送条件 setting-start
         */
        $set = new GTSettings();
        $set->setTtl(3600000); // 消息离线时间

        $strategy = new GTStrategy();
        $strategy->setDefault(GTStrategy::STRATEGY_GT_FIRST);
        $set->setStrategy($strategy);
        $push->setSettings($set);
        /**
         * 设置推送条件 setting-end
         */

        /**
         * 设置个推通道消息内容 PushMessage-start
         */
        $message = new GTPushMessage();

        // 通知消息
        if ($platform == 'android') {
            $notify = new GTNotification();
            $notify->setTitle($title);
            $notify->setBody($content);

            $notify->setChannelId("Default");
            $notify->setChannelName("Default");
            $notify->setChannelLevel(4);

            $notify->setClickType(GTThirdNotification::CLICK_TYPE_INTENT);
            $notify->setIntent("intent:#Intent;action=android.intent.action.oppopush;launchFlags=0x14000000;component={$this->config['package_name_android']}/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title={$title};S.content={$content};S.payload={$payload};end");
            $notify->setBadgeAddNum(1);
            if (is_numeric($payload)) {
                $notify->setNotifyId($payload);
            }
            $message->setNotification($notify);
        } else {
            // 纯透传消息
            $message->setTransmission('{"title": "' . $title . '", "content": "' . $content . '", "payload": "' . $payload . '"}');
        }

        $push->setPushMessage($message);
        /**
         * 设置个推通道消息内容 PushMessage-end
         */

        /**
         * 厂商推送消息参数 pushChannel-start
         */
        $pushChannel = new GTPushChannel();

        // 安卓
        $android = new GTAndroid();
        $ups     = new GTUps();
        // 通知消息
        $thirdNotification = new GTThirdNotification();
        $thirdNotification->setTitle($title);
        $thirdNotification->setBody($content);
        if (is_numeric($payload)) {
            $thirdNotification->setNotifyId($payload);
        }
        $thirdNotification->setClickType(GTThirdNotification::CLICK_TYPE_INTENT);
        $thirdNotification->setIntent("intent:#Intent;action=android.intent.action.oppopush;launchFlags=0x14000000;component={$this->config['package_name_android']}/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title={$title};S.content={$content};S.payload={$payload};end");
        $ups->setNotification($thirdNotification);

        // 厂商扩展参数
        $ups->addOption('HW', '/message/android/notification/badge/class', 'io.dcloud.PandoraEntry');
        $ups->addOption('HW', '/message/android/notification/badge/add_num', 1);
        $ups->addOption('HW', '/message/android/notification/importance', 'HIGH');
        $ups->addOption('VV', 'classification', 1);

        $android->setUps($ups);
        $pushChannel->setAndroid($android);

        // ios
        $ios = new GTIos();
        $ios->setType('notify');
        // $ios->setAutoBadge('+1');
        $ios->setPayload((string)$payload);
        $ios->setApnsCollapseId('collapse-id' . $payload);
        // aps设置
        $aps = new GTAps();
        $aps->setContentAvailable(0);
        $aps->setSound("default");

        // alert
        $alert = new GTAlert();
        $alert->setTitle($title);
        $alert->setBody($content);
        $aps->setAlert($alert);
        $ios->setAps($aps);
        $pushChannel->setIos($ios);

        $push->setPushChannel($pushChannel);

        /**
         * 厂商推送消息参数 pushChannel-end
         */

        return $push;
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }

    public function microTime()
    {
        [$usec, $sec] = explode(" ", microtime());
        $time = ($sec . substr($usec, 2, 3));
        return $time;
    }
}