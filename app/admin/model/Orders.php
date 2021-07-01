<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

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

            if ($user->amount2 < $amount) {
                throw new Exception('DTM余额不足');
            }
            $user->amount2 -= $amount;

            //产生订单记录
            $res = self::create([
                'uid'       =>  $user->id,
                'address'   =>  $user->address,
                'amount'    =>  $amount,
                'type'      =>  $type,
            ]);

            //插入资金日志
            MoneyLog::addLog($user->id,1, $amount,2, $res->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    //质押返利功能
    public static function rebate(int $order_id)
    {
        //查询订单
        $order = self::where('id', $order_id)->find();

        if (empty($order)) return false;

        //计算平均利息
        $average_lx = self::calc_lx($order);

        Db::startTrans();
        try {
            //记录返利时间
            $order->fl_time = time();
            //已返利天数+1
            $order->finish += 1;
            //提交订单修改
            $order->save();

            //用户DTM利息入账
            Users::changeAmount($order->uid,2, $average_lx);

            //插入资金日志
            MoneyLog::addLog($order->uid, 0, $average_lx, 1, $order->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Log::write($e->getMessage());
            return false;
        }
        return true;
    }

    //平均利息计算
    public static function calc_lx($order)
    {
        $config = sysconfig('other');

        if ($order->type == 1) {

            //计算总利息
            $all_lx = $order->amount * $config['zy1_lx'] / 100;
            //1天形式的，平均利息和总利息相等
            $average_lx = $all_lx;

        } else if ($order->type == 7) {

            //计算总利息
            $all_lx = $order->amount * $config['zy7_lx'] / 100;
            $average_lx = round($all_lx / 7, 4);

        } else if ($order->type == 15) {

            //计算总利息
            $all_lx = $order->amount * $config['zy15_lx'] / 100;
            $average_lx = round($all_lx / 15, 4);

        } else if ($order->type == 30) {

            //计算总利息
            $all_lx = $order->amount * $config['zy30_lx'] / 100;
            $average_lx = round($all_lx / 30, 4);

        }

        return $average_lx;
    }
}