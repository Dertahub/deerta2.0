<?php

namespace app\admin\model\keerta\score;

use think\Model;


class Order extends Model
{

    

    

    // 表名
    protected $name = 'keerta_score_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
//        'order_status_text',
//        'deliverytime_text'
    ];
    public function getOriginData()
    {
        return $this->origin;
    }
    protected static function init()
    {
        self::afterUpdate(function ($row) {
            $changedata = $row->getChangedData();
            $origin = $row->getOriginData();
            if (isset($changedata['order_status'])){
                if($origin['order_status'] == 1 && $changedata['order_status'] == 2){
                    self::where('id',$row['id'])->update(['deliverytime' => time()]);
                }
            }
        });
    }
    
    public function getOrderStatusList()
    {
        return ['1' => __('Order_status 1'), '2' => __('Order_status 2'), '3' => __('Order_status 3')];
    }


    public function getOrderStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['order_status'] ?? '');
        $list = $this->getOrderStatusList();
        return $list[$value] ?? '';
    }


    public function getDeliverytimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['deliverytime'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setDeliverytimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
