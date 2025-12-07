<?php

namespace app\admin\model\keerta\score;

use think\Model;


class Goods extends Model
{

    

    

    // 表名
    protected $name = 'keerta_score_goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
//        'switch_text'
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




    public function category()
    {
        return $this->belongsTo('app\admin\model\keerta\score\Category', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
