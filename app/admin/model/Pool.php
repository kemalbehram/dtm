<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class Pool extends TimeModel
{
    protected $name = "pool";

    //查询资金池总额
    public static function getAmount()
    {
        return self::where('name', 'pool')->value('amount');
    }

    //资金池入金
    public static function addPool($amount) {
        return self::where('name', 'pool')->inc('amount', $amount)->update();
    }

    //资金池消耗
    public static function decPool($amount) {
        return self::where('name', 'pool')->dec('amount', $amount)->update();
    }

    //获取今日已分红人数
    public static function getNum()
    {
        return self::where('name', 'pool')->value('num');
    }

    //新增分红人数
    public static function addNum()
    {
        return self::where('name', 'pool')->inc('num')->update();
    }

    //清空分红人数
    public static function clearNum()
    {
        return self::where('name', 'pool')->update([
            'num'   =>  0,
        ]);
    }

    //查询今日是否分红过
    public static function isReward()
    {
        $time = self::where('name', 'pool')->value('time');
        if ($time >= strtotime(date('Y-m-d'))) {
            return true;
        }
        return false;
    }
}