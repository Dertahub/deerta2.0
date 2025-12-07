<?php

namespace app\admin\model\keerta\goods;

use think\Exception;
use think\Model;


class Goods extends Model
{

    

    

    // 表名
    protected $name = 'keerta_goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
//        'is_hot_text',
//        'switch_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['surplus_amount' => $row['total_amount']]);
        });
        self::beforeWrite(function ($row) {
            if($row['interest_days'] < 1 || $row['start_cycle'] < 1){
                throw new Exception('投资收益天数设置错误');
            }
            // 判断interest_days和start_cycle是否成倍数关系
            if($row['start_cycle'] % $row['interest_days'] != 0){
                throw new Exception('投资周期设置错误，请重新设置周期和收益天数的倍数关系');
            }
        });
        self::afterWrite(function ($row) {
            $interest_num = bcdiv($row['start_cycle'], $row['interest_days'], 0);
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['interest_num' => $interest_num]);
        });
    }

    
    public function getIsHotList()
    {
        return ['0' => __('Is_hot 0'), '1' => __('Is_hot 1')];
    }

    public function getSwitchList()
    {
        return ['1' => __('Switch 1'), '0' => __('Switch 0')];
    }


    public function getIsHotTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_hot'] ?? '');
        $list = $this->getIsHotList();
        return $list[$value] ?? '';
    }


    public function getSwitchTextAttr($value, $data)
    {
        $value = $value ?: ($data['switch'] ?? '');
        $list = $this->getSwitchList();
        return $list[$value] ?? '';
    }




    public function goodscate()
    {
        return $this->belongsTo('app\admin\model\keerta\Goodscate', 'cate_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function goodstype()
    {
        return $this->belongsTo('app\admin\model\keerta\Goodstype', 'type_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function level()
    {
        return $this->belongsTo('app\admin\model\keerta\Level', 'level_ids', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
