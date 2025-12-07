<?php

namespace app\admin\model\keerta\redeem;

use think\Model;


class Redeem extends Model
{

    

    

    // 表名
    protected $name = 'keerta_redeem';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
//        'from_text',
//        'to_text'
    ];
    

    
    public function getFromList()
    {
        return ['money' => __('From money'), 'usdt' => __('From usdt'), 'withdraw_money' => __('From withdraw_money'), 'withdraw_usdt' => __('From withdraw_usdt')];
    }

    public function getToList()
    {
        return ['money' => __('To money'), 'usdt' => __('To usdt'), 'withdraw_money' => __('To withdraw_money'), 'withdraw_usdt' => __('To withdraw_usdt')];
    }


    public function getFromTextAttr($value, $data)
    {
        $value = $value ?: ($data['from'] ?? '');
        $list = $this->getFromList();
        return $list[$value] ?? '';
    }


    public function getToTextAttr($value, $data)
    {
        $value = $value ?: ($data['to'] ?? '');
        $list = $this->getToList();
        return $list[$value] ?? '';
    }




}
