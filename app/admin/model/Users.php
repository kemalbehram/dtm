<?php

namespace app\admin\model;

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

            $user = self::field('id,address,account1,account2')->find($result->id);

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

    //查询某账号下是否有3个排位下级
    public static function isCommonpathNum3(int $uid)
    {
        //节点成员
        $level1_uids = self::getLevel1($uid);

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
                MoneyLog::addLog($fid, 0, $award, 3, $fid);

                //查询上上级id，覆盖当前上级id
                $fid = self::getFid($fid);

            } else {
                break;
            }
        }
    }
}