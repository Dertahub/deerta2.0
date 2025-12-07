<?php

namespace app\admin\command;

use app\admin\model\keerta\score\Order;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class ConfirmReceipt extends Command
{

    protected function configure()
    {
        $this->setName('confirm_receipt')
            ->setDescription('confirm_receipt');
    }

    /**
     * @param Input $input
     */
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始执行');
        lock('confirm_receipt', 30);
        $order = Order::where('order_status', 2)
            ->where('createtime', '<', time() - 86400 * 14)
            ->find();
        if (!$order){
            $output->writeln('没有需要处理的订单');
            exit();
        }
        Order::where('order_status', 2)
            ->where('createtime', '<', time() - 86400 * 14)
            ->update(['order_status' => 3,'confirmtime' => time()]);
        $output->writeln('执行成功');
        exit();
    }

}