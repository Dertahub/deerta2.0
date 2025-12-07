<?php

namespace app\common\model;

use app\admin\model\keerta\Level;
use app\admin\model\keerta\level\Log;
use think\Model;

/**
 * 会员余额日志模型
 */
class MoneyLog extends Model
{

    // 表名
    protected $name = 'user_money_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
    ];

    /**
     * 余额操作
     *
     */
    public static function money($user_id, $money, $type, $field, $order_sn = '',$memo = ''){
        $user = User::where('id',$user_id)->find();
        if($type == 1){
            $memo = '充值';
        }elseif ($type == 2){
            $memo = empty($memo) ? '兑换' : $memo;
        }elseif ($type == 3){
            $memo = '签到成功送人民币';
        }elseif ($type == 4){
            $memo = '提现';
        }elseif($type == 5){
            if($field == 'money'){
                $user->rgtime = time();
                $memo = '购买产品';
            }elseif ($field == 'score'){
                $memo = '购买产品赠送积分';
            }elseif ($field == 'withdraw_money'){
                $memo = empty($memo) ? '购买产品赠送红包' : $memo;
            }
        }elseif ($type == 6){
            if ($money > 0){
                $memo = '余额宝转出';
            }else{
                $memo = '余额宝转入';
            }
        }elseif ($type == 7){
            $memo = empty($memo) ? '余额宝日结' : $memo;
        }elseif ($type == 8){
            $memo = '积分兑换';
        }elseif ($type == 9){
            $memo = '领取'.$order_sn.'团队奖励';
        }elseif ($type == 10){
            $memo = empty($memo) ? '团队返佣' : $memo;
        }elseif ($type == 12){
            $memo = empty($memo) ? '实名认证通过送红包' : $memo;
        }
        $beforeMoney = $user[$field];
        $afterMoney = $user[$field] + $money;
        if ($field == 'score'){
            if($money > 0){
                $total_score = $user['total_score'] + $money;
                $level = self::nextlevel($total_score);
                if($level != $user['level_id']){
                    $user->level_id = $level;
                    Log::create([
                        'user_id' => $user_id,
                        'level_id' => $level,
                        'memo' => '等级提升为'.$level,
                    ]);
                }
                $user->total_score = $total_score;
            }
        }
        MoneyLog::create([
            'user_id' => $user_id,
            'money' => $money,
            'before' => $beforeMoney,
            'after' => $afterMoney,
            'memo' => $memo,
            'type'=>$type,
            'order_sn'=>$order_sn,
            'symbol' => $field,
        ]);
        if ($beforeMoney != $afterMoney){
            $user->$field = $afterMoney;
            $user->save();
        }

    }

    /**
     * 根据积分获取等级
     *
     * @param int $score 积分
     * @return int
     */
    public static function nextlevel($score = 0)
    {
        $lv = Level::cache(true, 60)->column('id,score');
        $level = 0;
        foreach ($lv as $key => $value) {
            if ($score >= $value) {
                $level = $key;
            }
        }
        return $level;
    }
}
