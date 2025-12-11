<?php

namespace app\admin\command;

use getID3;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Test extends Command
{

    protected function configure()
    {
        $this->setName('test')
            ->setDescription('test');
    }

    /**
     * @param Input $input
     */
    protected function execute(Input $input, Output $output)
    {
        // 消息频率限制
        $now = microtime(true);
        $messageKey = md5('xxx');

        if (!isset($this->messageThrottle[$messageKey])) {
            $this->messageThrottle[$messageKey] = [
                'last_send_time' => 0,
                'send_count' => 0
            ];
        }

        $throttle = $this->messageThrottle[$messageKey];

        var_dump($throttle['last_send_time']);
        // 相同消息最小发送间隔100ms
        if ($now - $throttle['last_send_time'] < 0.5) {
            echo "相同内容消息频率限制，跳过: {$messageKey}\n";
            return;
        }
        // 更新节流器
        $throttle['last_send_time'] = $now;
        $throttle['send_count']++;
        $this->messageThrottle[$messageKey] = $throttle;
        echo "发送消息: {$now}\n";
        exit();
    }

}