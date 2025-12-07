<?php

namespace app\admin\command;

use app\admin\model\keerta\level\Team;
use app\admin\model\keerta\order\Interest;
use app\common\model\MoneyLog;
use app\common\model\User;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class GoodsBouns extends Command
{

    protected function configure()
    {
        $this->setName('goods_bouns')
            ->setDescription('goods_bouns');
    }

    /**
     * @param Input $input
     */
    protected function execute(Input $input, Output $output)
    {
        lock('goods_bouns', 90);
        $list = Interest::where('is_bouns', 0)
            ->where('bounstime','<', time())
            ->limit(50)
            ->select();
        if (!$list){
            $output->writeln('没有需要处理的数据');
            exit();
        }
        foreach ($list as $item) {
            $item->is_bouns = 1;
            $item->save();

            if ($item->type == 1){
                $memo = '利息返还';
            }elseif ($item->type == 2){
                $memo = '购买产品赠送红包';
            }elseif ($item->type == 3){
                $memo = '分红';
            }elseif ($item->type == 4){
                $memo = '本金返还';
            }

            MoneyLog::money($item->user_id, $item->amount, 5, 'withdraw_money', $item->order_sn, $memo);
            $output->writeln($item->id ."执行成功");
        }
        exit();
    }

}