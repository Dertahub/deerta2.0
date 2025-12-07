<?php

namespace app\admin\model\kefu;

use think\Model;
use app\admin\model\Admin;
use traits\model\SoftDelete;

class KeFuKbs extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'kefu_kbs';

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

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }


    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
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
