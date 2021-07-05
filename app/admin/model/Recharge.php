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

    //获取累计充值
    public static function getAllRecharge($uid)
    {
        return MoneyLog::where(['mtype' => 5, 'uid' => $uid])->sum('amount') ?? 0;
    }

    //充值排位
    public static function rechargeAddCommonpath(int $uid)
    {

        $config = sysconfig('other');

        $user = Users::find($uid);

        //如果已经有排位上级 或者 没有直推上级，都不处理
        if (!empty($user->cid) || empty($user->fid)) return false;

        //如果累计充值金额不达标
        if (Recharge::getAllRecharge($uid) < (float)$config['team_recharge']) return false;

        //排位到直推上级的下面
        Commonpath::addCommonpath($uid, $user->fid);

        return true;

    }

}