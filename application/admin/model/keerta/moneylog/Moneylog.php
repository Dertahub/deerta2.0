<?php

namespace app\admin\model\keerta\moneylog;

use think\Model;


class Moneylog extends Model
{

    

    

    // 表名
    protected $name = 'user_money_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'symbol_text'
    ];
    

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3'), '4' => __('Type 4'), '5' => __('Type 5'), '6' => __('Type 6'), '7' => __('Type 7'), '8' => __('Type 8'), '9' => __('Type 9'), '10' => __('Type 10'), '11' => __('Type 11'), '12' => __('Type 12'), '13' => __('释放钱包')];
    }

    public function getSymbolList()
    {
        return ['money' => __('Symbol money'), 'withdraw_money' => __('Symbol withdraw_money'), 'usdt' => __('Symbol usdt'), 'withdraw_usdt' => __('Symbol withdraw_usdt'), 'score' => __('Symbol score'), 'dsorb_money' => __('释放钱包')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }


    public function getSymbolTextAttr($value, $data)
    {
        $value = $value ?: ($data['symbol'] ?? '');
        $list = $this->getSymbolList();
        return $list[$value] ?? '';
    }




}
