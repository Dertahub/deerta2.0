<?php

namespace app\admin\model\keerta;

use think\Model;


class Notice extends Model
{

    

    

    // 表名
    protected $name = 'keerta_notice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
//        'switch_text',
//        'publishtime_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });
    }


    public function getCateList()
    {
        return Noticecate::where('switch', 1)->column('id,title');
//        return ['1' => __('Switch 1'), '0' => __('Switch 0')];
    }
    public function getSwitchList()
    {
        return ['1' => __('Switch 1'), '0' => __('Switch 0')];
    }


    public function getSwitchTextAttr($value, $data)
    {
        $value = $value ?: ($data['switch'] ?? '');
        $list = $this->getSwitchList();
        return $list[$value] ?? '';
    }


    public function getPublishtimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['publishtime'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPublishtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function noticecate()
    {
        return $this->belongsTo('Noticecate', 'cate_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
