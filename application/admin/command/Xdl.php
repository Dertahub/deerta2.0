<?php

namespace app\admin\command;

use app\admin\model\keerta\idle\Idleset;
use app\admin\model\keerta\idle\Order;
use app\admin\model\keerta\Level;
use app\common\model\MoneyLog;
use app\common\model\User;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Xdl extends Command
{

    protected function configure()
    {
        $this->setName('xdl')
            ->setDescription('xdl');
    }

    /**
     * @param Input $input
     */
    protected function execute(Input $input, Output $output)
    {
        $h = date('H');
        if ($h < 12 || $h > 23){
            $output->writeln('不在执行时间段内');
            exit();
        }

        lock('xdl', 30);
        $list = Order::where('status', 1)
            ->where(function($query) {
                $query->whereTime('lasttime', '<', 'today')
                    ->whereOr('lasttime', 'null');
            })
            ->whereTime('createtime','<', 'today')
            ->group('user_id')
            ->limit(50)
            ->field('user_id,sum(amount) as total_amount')
            ->select();
        if (empty($list)){
            $output->writeln('没有需要处理数据');
            exit();
        }
        $rate = Idleset::where('id', 1)->cache(true, 60)->value('day_profit_rate');
        $level_rate = Level::column('small_rate','id');
        foreach ($list as $item){
            $user = User::where('id', $item['user_id'])->find();
            $level_rate1 = $level_rate[$user['level_id']];
            $rate1 = bcdiv(($rate + $level_rate1), 100, 5);
            $money = bcmul($item['total_amount'], $rate1, 2);

            $memo = '余额宝日结(金额'.$item['total_amount'].'元利率'.$rate.'%加息率'.$level_rate1.'%获得'.$money.'元)';
            MoneyLog::money($item['user_id'], $money, 7, 'withdraw_money', $user['id'] . date('Ymd'), $memo);

            Order::where('user_id', $item['user_id'])
                ->where(function($query) {
                    $query->whereTime('lasttime', '<', 'today')
                        ->whereOr('lasttime', 'null');
                })
                ->whereTime('createtime','<', 'today')
                ->update(['lasttime' => time()]);

            $output->writeln($item['user_id'] ."执行成功");
        }
        exit();
    }

}