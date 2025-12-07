<?php

namespace app\admin\model\keerta\kline;

use think\Model;


class Kline extends Model
{

    

    

    // 表名
    protected $name = 'keerta_kline';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
