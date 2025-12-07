<?php

namespace app\admin\model\kefu;

use app\admin\model\User;
use think\Model;


class KeFuUser extends Model
{


    // 表名
    protected $name = 'kefu_user';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
