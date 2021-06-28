<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\Exception;
use think\facade\Db;

class Orders extends TimeModel
{
    protected $name = "orders";

    //查询某用户累积投资金额
    public static function cumulative_investment($uid)
    {
        return self::where('uid', $uid)->sum('amount');
    }

    //查询团队业绩
    public static function getTeamPerformance(array $uids)
    {
        return self::whereIn('uid', $uids)->sum('amount');
    }

    //质押投资
    public static function fund(int $uid, int $type, float $amount)
    {
        Db::startTrans();
        try {
            $user = Users::find($uid);

            if ($type == 1) {

                if ($user->amount1 < $amount) {
                    throw new Exception('可用账户余额不足');
                }
                $user->amount1 -= $amount;

            } else if ($type == 2) {

                if ($user->amount2 < $amount) {
                    throw new Exception('不可用账户余额不足');
                }
                $user->amount2 -= $amount;

            } else if ($type == 3) {

                if ($user->amount3 < $amount) {
                    throw new Exception('奖金账户余额不足');
                }
                $user->amount3 -= $amount;

            } else {
                throw new Exception('账户不存在');
            }

            //获得双倍额度
            $user->quota += $amount * 2;
            $user->save();

            //产生订单记录
            $res = self::create([
                'uid'       =>  $user->id,
                'address'   =>  $user->address,
                'amount'    =>  $amount,
            ]);

            //插入资金日志
            MoneyLog::addLog($user->id,1, $amount,2, $res->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }
}