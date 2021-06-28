<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class Recharge extends TimeModel
{

    protected $name = "recharge";

    protected $deleteTime = false;

    
    public static function getStatusList()
    {
        return [0 => '未处理', 1 => '已扫描'];
    }

    public static function getStateList()
    {
        return [0 => '未入账', 1 => '已入账'];
    }

    public static function getBlockTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

}