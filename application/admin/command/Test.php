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
        // 删除数据表
        \think\Db::execute("DROP TABLE IF EXISTS `think_file`");
//        Db::execute("DROP TABLE IF EXISTS `{$table}`");
        exit();
    }

}