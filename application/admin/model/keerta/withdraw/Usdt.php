<?php

namespace app\admin\model\keerta\withdraw;

use think\Model;


class Usdt extends Model
{

    

    

    // 表名
    protected $name = 'keerta_withdraw_usdt';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];

    public function realname()
    {
        return $this->belongsTo('app\admin\model\keerta\Realname', 'user_id', 'user_id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }


    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
