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

    //根据质押天数获取利息比例
    public static function getRatio(int $types)
    {
        $config = sysconfig('other');

        return $config['zy'.$types.'_lx'] ?? 0;
    }

    //质押DTM
    public static function fund(int $uid, int $types, float $amount)
    {
        Db::startTrans();
        try {
            $user = Users::find($uid);

            if ($user->amount2 < $amount) {
                throw new Exception('DTM余额不足');
            }
            $user->amount2 -= $amount;
            $user->save();

            //产生订单记录
            $res = self::create([
                'uid'       =>  $user->id,
                'address'   =>  $user->address,
                'amount'    =>  $amount,
                'types'     =>  $types,
                'ratio'     =>  self::getRatio($types),
            ]);

            //推荐奖清算
            Users::pushReward($user->id, $amount);

            //插入资金日志
            MoneyLog::addLog($user->id,1, $amount,2, $res->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    //USDT兑DTM 自动质押
    public static function auto_fund(int $uid, int $types, float $amount)
    {
        Db::startTrans();
        try {
            $user = Users::find($uid);

            //产生订单记录
            $res = self::create([
                'uid'       =>  $user->id,
                'address'   =>  $user->address,
                'amount'    =>  $amount,
                'types'      =>  $types,
                'ratio'     =>  self::getRatio($types),
                'auto'      =>  1,
            ]);

            //推荐奖清算
            Users::pushReward($user->id, $amount);

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

        //如果订单不存在
        if (empty($order)) return false;

        //如果订单已完结 或 状态不正确
        if (($order->finish >= $order->types) || $order->status <> 0) return false;

        //计算日平均利息
        $average_lx = round($order->amount * $order->ratio / 100 / $order->types,4);

        //没有利息不返利
        if (empty($average_lx)) return false;

        Db::startTrans();
        try {

            //记录订单返利时间
            $order->fl_time = time();

            //已返利天数+1
            $order->finish += 1;

            //已返利金额累加
            $order->fl_amount += $average_lx;

            //如果订单刚好完结
            if ($order->finish >= $order->types) {

                //变更订单状态为已到期
                $order->status = 1;

                //返还本金
                Users::changeAmount($order->uid, 2, $order->amount);

                //插入资金日志
                MoneyLog::addLog($order->uid, 0, $order->amount, 9, $order->id);

            }

            //提交订单修改
            $order->save();

            //用户DTM利息入账
            Users::changeAmount($order->uid,2, $average_lx);

            //插入资金日志
            MoneyLog::addLog($order->uid, 0, $average_lx, 1, $order->id);

            //收益奖清算
            Users::incomeReward($order->uid, $average_lx);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Log::write($e->getMessage());
            return false;
        }
        return true;
    }

    //订单提前解押
    public static function release($order_id)
    {
        Db::startTrans();
        try {

            $config = sysconfig('other');

            //查询订单信息
            $order = self::find($order_id);

            //如果订单不存在
            if (empty($order)) throw new Exception('订单不存在');

            //其他检查
            if (($order->finish >= $order->types) || $order->status <> 0) throw new Exception('订单已完结或状态不正确');

            //计算最终金额(可能是负数)
            //提前解押：扣10%本金，退回已派发利息。
            $amount = $order->amount - $order->amount * (float)$config['zy_jy'] / 100 - $order->fl_amount;

            //余额变更
            Users::changeAmount($order->uid, 2, $amount);

            //插入资金日志
            MoneyLog::addLog($order->uid, $amount > 0 ? 0 : 0, $amount, 10, $order->id);

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            Log::write($e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

}