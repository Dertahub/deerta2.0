<?php

namespace app\admin\model\keerta\treaty;

use think\Model;


class Sign extends Model
{

    

    

    // 表名
    protected $name = 'keerta_treaty_sign';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
