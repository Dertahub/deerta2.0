<?php
// +---------------------------------------------------------------------------------------------------------------------------
// | 本文件是《FastAdmin客服系统》的一部分，所有代码、文字、图片、样式、风格等版权归作者所有，如有复制、仿冒、抄袭、盗用，FastAdmin和作者将追究法律责任
// +---------------------------------------------------------------------------------------------------------------------------
// | @作者: 妙码生花 <1094963513@qq.com>
// +---------------------------------------------------------------------------------------------------------------------------
// | @唯一授权链接: https://www.fastadmin.net/store/kefu.html
// +---------------------------------------------------------------------------------------------------------------------------

use GatewayWorker\BusinessWorker;

// 获取插件配置
$kefu_config = get_addon_config('kefu');
// bussinessWorker 进程
$worker = new BusinessWorker();
// worker名称
$worker->name = 'KeFuBusinessWorker';
// bussinessWorker进程数量
$worker->count = $kefu_config['worker_process_number'];
// 服务注册地址
$worker->registerAddress = '127.0.0.1:' . $kefu_config['register_port'];
// 设置处理业务的类,此处制定Events的命名空间
$worker->eventHandler = 'addons\kefu\library\GatewayWorker\Applications\KeFu\Events';