<?php
use think\Env;
return [
    'connector'  => Env::get('queue.CONNECTOR', 'Redis'),          // Redis 驱动
    'expire'     => 0,             // 任务的过期时间，默认为60秒; 若要禁用，则设置为 null
    'default'    => 'default',    // 默认的队列名称
    'host'       => Env::get('app.REDIS_HOST', '127.0.0.1'),// redis 主机ip
    'port'       => Env::get('app.REDIS_PORT', '6379'),    // redis 端口
    'password'   => Env::get('app.REDIS_PASSWORD', ''),   // redis 密码
    'select'     => Env::get('app.REDIS_DB', 1),         // 使用哪一个 db，默认为 db0
    'timeout'    => 0,          // redis连接的超时时间
    'persistent' => false,
];
