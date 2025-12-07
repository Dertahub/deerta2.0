<?php

namespace app\admin\model\kefu;

use think\Model;
use app\admin\model\Admin;
use traits\model\SoftDelete;

class KeFuFastReply extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'kefu_fast_reply';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];


    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list  = $this->getStatusList();
        return $list[$value] ?? '';
    }


    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
