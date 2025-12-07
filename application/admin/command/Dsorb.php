<?php

namespace app\admin\command;

use app\common\model\MoneyLog;
use app\common\model\User;
use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Dsorb extends Command
{

    protected function configure()
    {
        $this->setName('dsorb')
            ->setDescription('dsorb');
    }

    /**
     * @param Input $input
     */
    protected function execute(Input $input, Output $output)
    {
        $key = 'dsorb_lock';
        $lock = Cache::get($key);
        if ($lock) {
            $output->writeln('释放锁定中！');
            exit();
        }
        $h = date('H');
        $config = Db::name('keerta_dsorb')
            ->where('id', 1)
            ->cache('keerta_dsorb', 60)
            ->find();
        if($h < $config['time']){
            $output->writeln('当前时间不可执行释放！');
            exit();
        }
        if($config['dsorb_ratio'] == 0){
            $output->writeln('当前释放比例为0不可执行释放！');
            exit();
        }

        $ratio = $config['dsorb_ratio'];
        // 数据集分批处理
        User::where('dsorb_money', '>', 0)
            ->whereTime('last_dsorb_time','<', 'today')
            ->chunk(100, function($users) use ($ratio){
                foreach ($users as $item) {
                    $user_ratio = $item['dsorb_ratio'] > 0 ? $item['dsorb_ratio'] : $ratio;
                    $user_ratio = min($user_ratio / 100, 1);
                    echo $item['id'].'开始释放,比例：'.$user_ratio.PHP_EOL;
                    $money = bcmul($item['dsorb_money'], $user_ratio, 2);
                    if($money > 0)
                    {
                        MoneyLog::money($item->id, -$money, 13, 'dsorb_money', $item->id . date('Y-m-d'), '每日释放');
                        MoneyLog::money($item->id, $money, 13, 'withdraw_money', $item->id . date('Y-m-d'), '每日释放');
                        $item->last_dsorb_time = time();
                        $item->save();
                    }
                    echo $item['id'].'释放完成,金额：'.$money.PHP_EOL;
                }
            });

        Cache::set($key, 1, 600);

        exit();
    }

}