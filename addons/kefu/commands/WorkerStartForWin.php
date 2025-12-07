<?php
// +---------------------------------------------------------------------------------------------------------------------------
// | 本文件是《FastAdmin客服系统》的一部分，所有代码、文字、图片、样式、风格等版权归作者所有，如有复制、仿冒、抄袭、盗用，FastAdmin和作者将追究法律责任
// +---------------------------------------------------------------------------------------------------------------------------
// | @作者: 妙码生花 <1094963513@qq.com>
// +---------------------------------------------------------------------------------------------------------------------------
// | @唯一授权链接: https://www.fastadmin.net/store/kefu.html
// +---------------------------------------------------------------------------------------------------------------------------

namespace addons\kefu\commands;

use Workerman\Worker;
use think\console\Input;
use think\console\Output;
use think\console\Command;
use think\console\input\Argument;

class WorkerStartForWin extends Command
{
    protected function configure(): void
    {
        $this->setName('WorkerStartForWin')
            ->addArgument('server', Argument::REQUIRED, "The server to start.")
            ->setDescription('Worker server');
    }

    protected function execute(Input $input, Output $output): void
    {
        $server = trim($input->getArgument('server'));
        $server = __DIR__ . DS . "servers" . DS . "$server.php";

        if (!file_exists($server)) {
            $output->writeln("<error>$server file does not exist.</error>");
        }

        require_once __DIR__ . '/../library/GatewayWorker/vendor/autoload.php';
        require_once $server;

        Worker::runAll();
    }
}