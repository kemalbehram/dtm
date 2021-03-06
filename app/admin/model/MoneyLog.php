<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\Exception;

class MoneyLog extends TimeModel
{
    protected $name = "money_log";

    //资金日志
    public static function addLog($uid, $direction, $amount, $mtype, $ext_id = 0)
    {
        try {
            self::create([
                'uid'       =>  $uid,
                'direction' =>  $direction,
                'amount'    =>  $amount,
                'mtype'     =>  $mtype,
                'content'   =>  self::$mtype[$mtype],
                'ext_id'    =>  $ext_id,
            ]);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        return true;
    }

    //收益统计
    public static function getAward(int $uid):array
    {
        //质押收益
        $arr['zy_award'] = self::where(['mtype' => 1, 'uid' => $uid])->sum('amount');
        //推荐奖
        $arr['tj_award'] = self::where(['mtype' => 3, 'uid' => $uid])->sum('amount');
        //收益奖
        $arr['sy_award'] = self::where(['mtype' => 4, 'uid' => $uid])->sum('amount');
        //分红
        $arr['fh_award'] = self::where(['mtype' => 8, 'uid' => $uid])->sum('amount');
        //累计
        $arr['all_award'] = $arr['zy_award'] + $arr['tj_award'] + $arr['sy_award'] + $arr['fh_award'];

        return $arr;
    }
}