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

class WorkerStart extends Command
{
    protected function configure()
    {
        $this->setName('kefu')
            ->addArgument('action', Argument::OPTIONAL, "action  start [d]|stop|restart|status")
            ->addArgument('type', Argument::OPTIONAL, "d -d")
            ->setDescription('KeFu 会话服务');
    }

    protected function execute(Input $input, Output $output)
    {
        global $argv;
        $action = trim($input->getArgument('action'));
        $type   = trim($input->getArgument('type')) ? '-d' : '';

        $argv[0] = 'chat';
        $argv[1] = $action;
        $argv[2] = $type ? '-d' : '';
        $this->start();
    }

    private function start()
    {
        if (strpos(strtolower(PHP_OS), 'win') === 0) {
            exit("Windows下不支持窗口启动，请手动运行(not support windows, please use)：public/kefu_start_for_win.bat\n");
        }

        // 检查扩展
        if (!extension_loaded('pcntl')) {
            exit("Please install pcntl extension. See http://doc.workerman.net/appendices/install-extension.html\n");
        }

        if (!extension_loaded('posix')) {
            exit("Please install posix extension. See http://doc.workerman.net/appendices/install-extension.html\n");
        }

        require_once __DIR__ . '/../library/GatewayWorker/vendor/autoload.php';

        // 加载所有的服务启动文件
        foreach (glob(__DIR__ . '/servers/*.php') as $start_file) {
            require_once $start_file;
        }

        // 运行所有服务
        Worker::runAll();
    }
}