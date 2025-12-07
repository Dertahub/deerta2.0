<?php
// +---------------------------------------------------------------------------------------------------------------------------
// | 本文件是《FastAdmin客服系统》的一部分，所有代码、文字、图片、样式、风格等版权归作者所有，如有复制、仿冒、抄袭、盗用，FastAdmin和作者将追究法律责任
// +---------------------------------------------------------------------------------------------------------------------------
// | @作者: 妙码生花 <1094963513@qq.com>
// +---------------------------------------------------------------------------------------------------------------------------
// | @唯一授权链接: https://www.fastadmin.net/store/kefu.html
// +---------------------------------------------------------------------------------------------------------------------------

use GatewayWorker\Gateway;

// gateway 进程
$kefu_config = get_addon_config('kefu');

$context   = [];
$ssl_start = false;
if ($kefu_config['wss_switch'] && $kefu_config['ssl_cert'] && $kefu_config['ssl_cert_key']) {
    $context ['ssl'] = [
        // 使用绝对路径
        'local_cert'  => $kefu_config['ssl_cert'], // 也可以是crt文件
        'local_pk'    => $kefu_config['ssl_cert_key'],
        'verify_peer' => false,
        //'allow_self_signed' => true, //如果是自签名证书开启此选项
    ];

    $ssl_start = true;
}

$gateway = new Gateway("websocket://0.0.0.0:" . $kefu_config['websocket_port'], $context);

if ($ssl_start) {
    // 开始SSL
    $gateway->transport = 'ssl';
}

// gateway名称，status方便查看
$gateway->name = 'KeFuGateway' . ($ssl_start ? '-wss' : '');

// gateway进程数
$gateway->count = $kefu_config['gateway_process_number'];

// 本机ip，分布式部署时使用内网ip
$gateway->lanIp = '127.0.0.1';

// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口
$gateway->startPort = $kefu_config['internal_start_port'];

// 服务注册地址
$gateway->registerAddress = '127.0.0.1:' . $kefu_config['register_port'];

// 心跳间隔
$gateway->pingInterval = 30;

$gateway->pingNotResponseLimit = 1;

// 心跳数据
$gateway->pingData = '';
