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

            //公排关系处理
            Commonpath::addCommonpath($result->id, $fid);

            $user = self::field('id,address,amount1,amount2,amount3,quota')->find($result->id);

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

    //获取某用户实际释放金额
    public static function getRealAmount(float $quota, int $uid)
    {
        //获取配置
        $config = sysconfig('other');

        //默认释放比例
        $release_ratio = floatval($config['release_ratio']);

        $user = self::find($uid);
        //如果用户有独立设置释放比例
		$user->release_ratio = floatval($user->release_ratio);
        if ($user->release_ratio) {
            $release_ratio = $user->release_ratio;
        }
		
		//百分比单位转换
		$release_ratio /= 100;

        //额度低于2万U，正常释放
        //额度超过2万u的部分，静态释放的比例减为原来比例的 X %
        //额度超过5万u的部分，静态释放的比例减为原来比例的 Y %
        if ($quota <= 20000) {
            $amount = $quota * $release_ratio;
        } else if ($quota > 20000 && $quota <= 50000) {
            $amount = 20000 * $release_ratio + ($quota - 20000) * $release_ratio * floatval($config['dec1_ratio']) / 100;
        } else if ($quota > 50000) {
            $amount = 20000 * $release_ratio + 50000 * $release_ratio * floatval($config['dec1_ratio']) / 100 + ($quota - 50000) * $release_ratio * floatval($config['dec2_ratio']) / 100;
        }

        $amount = round($amount, 4);

        return $amount;
    }

    //奖励入账2个账户
    public static function income(int $uid, float $amount)
    {
        try {
            //获取配置
            $config = sysconfig('other');
            //所有的奖励的 拿出 X% 放到一个 USDT不可用账户 里面，这个钱只能投资，不能提现
            $unavailable_ratio = floatval($config['unavailable_ratio']) / 100;

            //USDT不可用账户入账
            self::changeAmount($uid, 2,$amount * $unavailable_ratio);
            //USDT奖金账户入账
            self::changeAmount($uid, 3,$amount * (1 - $unavailable_ratio));
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        return true;
    }

    //静态奖返利功能
    public static function rebate(int $uid)
    {
        $user = self::find($uid);

        //如果账户额度低于1不释放
        if ($user->quota < 1) return false;

        //取得用户实际释放金额
        $amount = self::getRealAmount($user->quota, $user->id);

        Db::startTrans();
        try {
            //扣除释放额度
            $user->quota -= $amount;
            //记录返利时间
            $user->fl_time = time();
            //提交修改
            $user->save();

            //收益入账2个账户
            self::income($user->id, $amount);

            //直推奖励清算
            self::pushReward($user->id, $amount);

            //管理奖清算
            self::management_award($user->id, $amount);

            //插入资金日志
            MoneyLog::addLog($user->id, 0, $amount, 1, $user->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Log::write($e->getMessage());
            return false;
        }
        return true;
    }

    //直推返利功能
    public static function pushReward(int $uid, float $amount)
    {
        //获取配置
        $config = sysconfig('other');
        //直推获得下级会员释放部分的 X %
        $zt_ratio = floatval($config['zt_ratio']) / 100;

        //查询上级
        $fid = self::getFid($uid);

        //如果上级存在 且 直推奖励已开启
        if ($fid > 0 && $zt_ratio > 0) {
            //获取上级信息
            $fuser = self::find($fid);

            //计算上级累计投资金额
            $cumulative_investment = Orders::cumulative_investment($fid);

            //计算直推奖励
            $award = $amount * $zt_ratio;

            //上级发生了投资才有奖励
            //拿直推奖励需要扣掉上级额度
            //上级额度足够才有奖励
            if ($cumulative_investment > 0 && ($fuser->quota >= $award)) {

                //直推奖励入账
                self::income($fid, $award);

                //扣掉上级额度
                $fuser->quota -= $award;
                $fuser->save();

                //插入资金日志
                MoneyLog::addLog($fid, 0, $award, 3, $uid);
            }
        }
    }


    //管理奖功能
    public static function management_award(int $uid, float $amount)
    {
        try {
            //获取配置
            $config = sysconfig('other');

            //查询累计投资金额
            $cumulative_investment = Orders::cumulative_investment($uid);

            //管理奖每日封顶是自己累积投资额的 X%
            $capped = $cumulative_investment * (float)$config['glj_fd_ratio'] / 100;

            //今日已获得的管理奖总额
            $today_award = MoneyLog::whereDay('create_time')->where(['mtype' => 4, 'uid' => $uid])->sum('amount');

            //封顶检测，(没有等于的情况因为0 = 0)
            if ($today_award > $capped) return false;

            //查询用户的所有公排上级
            $uids = Commonpath::where('member_uid', $uid)->column('uid');
            //去重
            $uids = array_unique(array_values($uids));

            //公排上级检测
            if (empty($uids)) return false;

            //分别对每个公排上级进行判断
            foreach ($uids as $v) {
                //查询管理奖等级
                $level = self::management_level($v);

                //过滤用户
                if (empty($level)) continue;

                //计算管理奖
                $award = $amount * $level['ratio'];

                //查询用户信息
                $user = self::find($v);

                //过滤额度不足的
                if ($award > $user->quota) continue;

                //发放管理奖
                self::income($v, $award);

                //扣除额度
                $user->quota -= $award;
                $user->save();

                //插入资金日志
                MoneyLog::addLog($v,0,$award,4, $uid);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    //查询管理奖等级
    //示例：
    //v1，本人累计投资300u以上，且小区累计业绩达到300u，拿小区里面的每一个人投资奖励收益的10%
    //v2，本人累计投资1000u以上，且小区累计业绩达到3000u，拿15%
    //v3，本人累计投资3000u以上，且小区累积业绩达到30000万u，拿20%
    public static function management_level(int $uid)
    {
        //获取配置
        $config = sysconfig('other');

        //查询累计投资金额
        $cumulative_investment = Orders::cumulative_investment($uid);

//        halt($cumulative_investment);

        //查询AB线业绩
        $getAbPerformance = Commonpath::getAbPerformance($uid);

//        halt($getAbPerformance);

        //如果业绩不存在
        if (empty($getAbPerformance))  return [];

        //业绩数组
        $arr = [$getAbPerformance['a_performance'], $getAbPerformance['b_performance']];

        //取得小区业绩
        $minPerformance = min($arr);

        //等级判断
        if (
            $cumulative_investment >= (float)$config['v3_ljtz'] &&
            $minPerformance >= (float)$config['v3_yj']
        ){
            return ['level' => 3, 'ratio' => (float)$config['v3_sy_ratio'] / 100];
        } else if (
            $cumulative_investment >= (float)$config['v2_ljtz'] &&
            $minPerformance >= (float)$config['v2_yj']
        ){
            return ['level' => 2, 'ratio' => (float)$config['v2_sy_ratio'] / 100];
        } else if (
            $cumulative_investment >= (float)$config['v1_ljtz'] &&
            $minPerformance >= (float)$config['v1_yj']
        ){
            return ['level' => 1, 'ratio' => (float)$config['v1_sy_ratio'] / 100];
        } else {
            return [];
        }
    }
}