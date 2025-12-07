<?php

namespace app\admin\model\keerta\level;

use think\Model;


class Team extends Model
{

    // 表名
    protected $name = 'keerta_level_team';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    
    public static function getTeam($id)
    {
        return self::where('id',$id)->cache( true, 60)->find();
    }






}
