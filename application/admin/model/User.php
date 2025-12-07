<?php

namespace app\admin\model;

use app\common\model\MoneyLog;
use app\common\model\ScoreLog;
use think\Model;

class User extends Model
{

    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'prevtime_text',
        'logintime_text',
        'jointime_text'
    ];

    public function getOriginData()
    {
        return $this->origin;
    }

    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            //如果有修改密码
            if (isset($changed['password'])) {
                if ($changed['password']) {
                    $salt = $row->salt;
                    $row->password = \app\common\library\Auth::instance()->getEncryptPassword($changed['password'], $salt);
                } else {
                    unset($row->password);
                }
            }
        });


        self::beforeUpdate(function ($row) {
            $changedata = $row->getChangedData();
            $origin = $row->getOriginData();
            if (isset($changedata['money']) && (function_exists('bccomp') ? bccomp($changedata['money'], $origin['money'], 2) !== 0 : (double)$changedata['money'] !== (double)$origin['money'])) {
                self::money($row, $changedata, $origin, 'money');
            }
            if (isset($changedata['score']) && (int)$changedata['score'] !== (int)$origin['score']) {
                self::money($row, $changedata, $origin, 'score');
            }
            if (isset($changedata['usdt']) && $changedata['usdt'] !== $origin['usdt']) {
                self::money($row, $changedata, $origin, 'usdt');
            }
            if (isset($changedata['withdraw_money']) && $changedata['withdraw_money'] !== $origin['withdraw_money']) {
                self::money($row, $changedata, $origin, 'withdraw_money');
            }
            if (isset($changedata['withdraw_usdt']) && $changedata['withdraw_usdt'] !== $origin['withdraw_usdt']) {
                self::money($row, $changedata, $origin, 'withdraw_usdt');
            }
        });
    }

    /*
     * 余额处理
     */
    public static function money($row, $changedata, $origin, $symbol)
    {
        MoneyLog::create([
            'user_id' => $row['id'],
            'money' => $changedata[$symbol] - $origin[$symbol],
            'before' => $origin[$symbol],
            'after' => $changedata[$symbol],
            'memo' => '管理员变更金额',
            'type'=>11,
            'symbol' => $symbol,
            'order_sn'=>'admin'
        ]);
    }
    /**
     * @return array
     */

    public function getGenderList()
    {
        return ['1' => __('Male'), '0' => __('Female')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }


    public function getPrevtimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['prevtime'] ?? "");
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getLogintimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['logintime'] ?? "");
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getJointimeTextAttr($value, $data)
    {
        $value = $value ? $value : ($data['jointime'] ?? "");
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPrevtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setLogintimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setJointimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setBirthdayAttr($value)
    {
        return $value ? $value : null;
    }

    public function group()
    {
        return $this->belongsTo('UserGroup', 'group_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function realname()
    {
        return $this->belongsTo('app\admin\model\keerta\Realname', 'id', 'user_id', [], 'LEFT')->setEagerlyType(0);
    }
}
