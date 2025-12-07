<?php

namespace app\admin\model\keerta;

use think\Model;


class Banner extends Model
{

    

    

    // 表名
    protected $name = 'keerta_banner';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
//        'switch_text',
//        'type_text',
//        'seat_text'
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

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }
    public function getJumpList()
    {
        return ['1' => __('不跳转'), '2' => __('外链')];
    }

    public function getSeatList()
    {
        return ['1' => __('Seat 1'), '2' => __('Seat 2'), '3' => __('Seat 3'), '4' => __('Seat 4')];
    }


    public function getSwitchTextAttr($value, $data)
    {
        $value = $value ?: ($data['switch'] ?? '');
        $list = $this->getSwitchList();
        return $list[$value] ?? '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }


    public function getSeatTextAttr($value, $data)
    {
        $value = $value ?: ($data['seat'] ?? '');
        $list = $this->getSeatList();
        return $list[$value] ?? '';
    }




}
