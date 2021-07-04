<?php

namespace app\admin\model;

use app\admin\service\TriggerService;
use app\common\model\TimeModel;
use think\Exception;
use think\facade\Db;
use think\facade\Log;
use app\admin\model\Commonpath;

class Users extends TimeModel
{
    protected $name = "users";

    //创建用户
    public static function userCreate($address, $faddress)
    {
        Db::startTrans();
        try {
            $fid = 0;

            if (strlen($faddress) == 34) {
                $fid = self::where('address', $faddress)->value('id') ?? 0;
            }

            $result = Users::create([
                'address'   =>  $address,
                'fid'       =>  $fid,
            ]);

            //注册关系处理
            Regpath::addRegpath($result->id, $fid);

            $user = self::field('id,address,amount1,amount2')->find($result->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            throw new Exception($e->getMessage());
        }
        return $user;
    }

    //id查地址
    public static function id2address($uid)
    {
        return self::where('id', $uid)->value('address') ?? '';
    }

    //地址查id
    public static function address2id($address)
    {
        return self::where('address', $address)->value('id') ?? 0;
    }

    //查询某账号上级
    public static function getFid($uid)
    {
        return self::where('id', $uid)->value('fid');
    }

    //改变各账户余额
    public static function changeAmount(int $uid, int $type, float $amount):bool
    {
        if ($amount == 0) return false;

        try {
            self::where('id', $uid)->update([
                'amount'.$type  =>  Db::raw('amount'.$type.'+'.$amount),
            ]);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        return true;
    }

    //查询某账号下面 是否至少3个排位下级
    public static function isCommonpathNum3(int $uid)
    {
        //节点成员
        $level1_uids = self::getLevel1($uid);

        //是否大于3个
        if (count($level1_uids) >= 3) {
            return true;
        }

        return false;
    }

    //推荐奖功能
    public static function pushReward(int $uid, float $amount)
    {
        //获取配置
        $config = sysconfig('other');

        //查询上级id
        $fid = self::getFid($uid);

        //向上奖励三代
        foreach (range(1,3) as $v) {

            //查询上级信息
            $parent_info = self::find($fid);

            //如果上级存在且奖励开启
            if (!empty($parent_info) && !empty($config['ttj'.$v])) {

                //计算奖金
                $award = $amount * $config['ttj'.$v] / 100;

                //收益入账
                self::changeAmount($fid, 2, $award);

                //插入资金日志
                MoneyLog::addLog($fid, 0, $award, 3, $fid);

                //查询上上级id，覆盖当前上级id
                $fid = self::getFid($fid);

            } else {
                break;
            }
        }
    }

    //收益奖功能
    public static function incomeReward(int $uid, float $amount)
    {
        //获取配置
        $config = sysconfig('other');

        //查询上级id
        $fid = self::getFid($uid);

        //向上奖励三代
        foreach (range(1,3) as $v) {

            //查询上级信息
            $parent_info = self::find($fid);

            //如果上级存在且奖励开启
            if (!empty($parent_info) && !empty($config['syj'.$v])) {

                //计算奖金
                $award = $amount * $config['syj'.$v] / 100;

                //收益入账
                self::changeAmount($fid, 2, $award);

                //插入资金日志
                MoneyLog::addLog($fid, 0, $award, 4, $fid);

                //查询上上级id，覆盖当前上级id
                $fid = self::getFid($fid);

            } else {
                break;
            }
        }
    }

    //USDT兑换DTM
    public static function usdt2dtm($uid, $amount)
    {
        Db::startTrans();
        try {
            $config = sysconfig('other');

            //查询用户信息
            $user = self::find($uid);

            //余额校验
            if ($user->amount1 < $amount) {
                throw new Exception('USDT余额不足');
            }

            //计算获得DTM数量
            $dtm_amount = $amount / floatval($config['dtm_usdt_price']);

            if ($dtm_amount <=0 ) throw new Exception('数量计算出错');

            //买入扣10%手续费剩下的90%，再拿50%自动购买7天的质押，剩下的才是实际到账的。
            //兑换DTM手续费
            $buy_fee = $dtm_amount * floatval($config['buy_fee']) / 100;
            //质押金额
            $zy_amount = ($dtm_amount - $buy_fee) * floatval($config['auto_buy_bl']) / 100;
            //实际到账
            $real_amount = $dtm_amount - $buy_fee - $zy_amount;

            //开始质押
            Orders::fund($uid, 7, $zy_amount);
            //DTM到账
            $user->amount2 += $real_amount;
            $user->save();
            //手续费资金池到账
            Pool::addPool($buy_fee);

            //每次兑换后DTM价格涨一点
            $num = floatval($config['dtm_usdt_price']) * floatval($config['dtm_usdt_incdec']) / 100;
            //变更价格
            SystemConfig::where('name', 'dtm_usdt_price')->inc('value', $num)->update();
            //刷新配置缓存
            TriggerService::updateSysconfig();

            //资金日志
            MoneyLog::addLog($uid, 1, $buy_fee, 11, 0);
            MoneyLog::addLog($uid, 1, $zy_amount, 12, 0);
            MoneyLog::addLog($uid, 0, $real_amount, 13, 0);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    //DTM兑换USDT
    public static function dtm2usdt($uid, $amount)
    {
        Db::startTrans();
        try {
            $config = sysconfig('other');

            //查询用户信息
            $user = self::find($uid);

            //余额校验
            if ($user->amount2 < $amount) {
                throw new Exception('DTM余额不足');
            }

            //计算获得USDT数量
            $usdt_amount = $amount * floatval($config['dtm_usdt_price']);

            if ($usdt_amount <=0 ) throw new Exception('数量计算出错');

            //卖出要扣1%或者50% 手续费，扣1%，需要下面有三个会员（可以是上级给他安排，也可以是自己直推）。扣50%是下面没有三个人排队。
            //默认手续费50% (USDT)
            $sell_fee = $usdt_amount * floatval($config['sell_fee2']) / 100;

            //下面有三个会员手续费1% (USDT)
            if (self::isCommonpathNum3($uid)) {
                $sell_fee = $usdt_amount * floatval($config['sell_fee1']) / 100;
            }

            //实际到账
            $real_amount = $usdt_amount - $sell_fee;

            //USDT到账
            $user->amount1 += $real_amount;
            $user->save();
            //手续费资金池到账，换算成DTM
            Pool::addPool($sell_fee / floatval($config['dtm_usdt_price']));

            //每次兑换后DTM价格跌一点，受地板价限制
            //计算减少金额
            $num = floatval($config['dtm_usdt_price']) * floatval($config['dtm_usdt_incdec']) / 100;

            //如果减少后还是高于地板价才能继续
            if (floatval($config['dtm_usdt_price']) - $num >= $config['min_dtm_usdt_price']) {

                //变更价格
                SystemConfig::where('name', 'dtm_usdt_price')->dec('value', $num)->update();
                //刷新配置缓存
                TriggerService::updateSysconfig();

            }

            //资金日志
            MoneyLog::addLog($uid, 1, $sell_fee, 14, 0);
            MoneyLog::addLog($uid, 0, $real_amount, 15, 0);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    //分红统计
    public static function dividends_statistics()
    {
        //满足条件的用户集合
        $arr = [];

        $config = sysconfig('other');

        //先筛选DTM余额大于100的用户
        $users = self::where('amount2', '>=', floatval($config['fh_ye_min']))->select();

        //如果存在
        if (!$users->isEmpty()) {

            //再次筛选有3个排位下级的用户，并计算分红权
            foreach ($users as $user) {

                if (!self::isCommonpathNum3($user->id)) continue;

                //计算分红权，向下取整
                $dividend_right = floor($user->amount2 / 100);

                //登记用户
                $arr[] = ['uid' => $user->id, 'dividend_right' => $dividend_right];

            }

        }

        return $arr;
    }

}