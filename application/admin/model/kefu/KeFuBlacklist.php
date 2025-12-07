<?php

namespace app\admin\model\kefu;

use think\Model;
use app\admin\model\Admin;

class KeFuBlacklist extends Model
{

    // 表名
    protected $name = 'kefu_blacklist';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function kefuuser()
    {
        return $this->belongsTo('KeFuUser', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
