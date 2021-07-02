<?php

namespace app\admin\controller;

use app\admin\model\MoneyLog;
use app\admin\model\Pool;
use app\admin\model\Regpath;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\model\Recharge;
use app\admin\model\Users;
use app\admin\model\Orders;

//定时任务
class Task extends AdminController
{
    //采集AUK充值记录
    public function task1() {
        $address = sysconfig('other','recharge_address');
        $url = 'https://api.trongrid.io/v1/accounts/'.$address.'/transactions/trc20?only_confirmed=true&only_to=true&limit=30&contract_address=TGkp9HB9v5DDEJqCqoS7iLYnCobAnqG7zN';
        $header = [
            //需要去trongrid用邮箱申请key，不要用默认的，有请求频率限制
            'TRON-PRO-API-KEY'   =>  '7b55c44b-3ffa-435a-9f2d-e79341c498b6',
        ];
        $result = curl_get($url, $header);
        if (!empty($result['data'])) {
            $arr = [];
            foreach ($result['data'] as $v) {
                $amount = $v['value'] / 1000000000000000000;
                $arr[] = [
                    'tx'            =>  $v['transaction_id'],
                    'symbol'        =>  $v['token_info']['symbol'],
                    'from_address'  =>  $v['from'],
                    'to_address'    =>  $v['to'],
                    'amount'        =>  $amount,
                    'block_time'    =>  substr($v['block_timestamp'],0,10),
                    'create_time'   =>  time(),
                ];
            }
            Db::name('recharge')->extra('IGNORE')->insertAll($arr);
        }
        return 'success';
    }

    //检测并入库充值记录
    public function task2()
    {
        //筛选所有未处理的数据
        $data = Recharge::where('status', 0)->select();

        //循环处理订单
        foreach ($data as $v) {
            //获取用户资料
            $user = Users::where('address', $v->from_address)->find();

            //如果用户不存在，仅标记为已处理后抛弃
            if (empty($user)) {
                $v->status = 1;
                $v->save();
                continue;
            }

            //开启事务
            Db::startTrans();
            try {
                //资金入账
                $user->amount1 += $v->amount;

                //累加 用户累计充值金额
                $user->all_recharge += $v->amount;

                //提交数据
                $user->save();

                //插入资金日志
                MoneyLog::addLog($user->id,0, $v->amount,5, $v->id);

                //充值记录标记为已处理、已入账
                $v->status = 1;
                $v->state = 1;
                $v->save();

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                continue;
            }
        }
        return 'success';
    }

    //DTM质押返利（每日零点后执行）
    public function task3()
    {
        //返利人数
        $num = 0;

        //获取所有状态为派息中的订单
        //为方便客户测试，去掉了每日一次结算限制，改成了请求一次结算一次，后期改回
        $order_ids = Orders::where('status',0)
//                ->where('fl_time', '<', strtotime(date('Y-m-d')))
                ->column('id');
        foreach ($order_ids as $order_id) {
            //执行返利
            $result = Orders::rebate($order_id);
            $result && $num++;
        }

        return '今日返利人数：'.$num.'人';

    }

    //手续费资金池每日分红
    public function task4()
    {
        //取得相关配置
        $config = sysconfig('other');

        //待分红集合
        $wait = [];

        //如果今日未分红
        if (!Pool::isReward()) {

            //获取所有会员
            $users = Users::select();

            foreach ($users as $user) {

                //查询自己的累计投资额
                $cumulative_investment = Orders::cumulative_investment($user->id);

                //自己+直推总投资金额
                $all = $cumulative_investment;

                //如果大于设定额度
                if ($cumulative_investment >= floatval($config['fen1'])) {

                    //查询直推人员名单
                    $level1 = Regpath::getLevelUids($user->id, 1);

                    //如果直推达到设定数量
                    if (count($level1) >= intval($config['fen2'])) {

                        //统计达到累计投资额条件的直推人员人数
                        $num = 0;
                        //统计达到管理奖等级条件的直推人员人数
                        $v3_num = 0;

                        foreach ($level1 as $v) {

                            //查询直推人员的累计投资额
                            $zhi_cumulative_investment = Orders::cumulative_investment($v);
                            //如果直推人员的累计投资额达到设定条件，就标记一下
                            ($zhi_cumulative_investment >= floatval($config['fen3'])) && $num++;

                            //累加自己+直推人员总投资金额
                            $all += $zhi_cumulative_investment;

                            //查询直推人员的管理奖信息
                            $level_info = Users::management_level($v);
                            //如果等级达到V3就标记一下
                            ($level_info['level'] == 3) && $v3_num++;

                        }

                        //如果被标记的人数也正常
                        if ($num >= intval($config['fen2'])) {

                            //获取今日分红人数
                            $today_reward_num = Pool::getNum();

                            //自己是前100人，直接加入分红集合
                            if ($today_reward_num <= intval($config['fen4'])) {

                                $wait[] = ['uid' => $user->id, 'all' => $all];

                            } else {

                                //否则，除了需要满足上述条件外，还必须有足够的直推人数达到v3级别才能加入分红集合
                                //如果被标记的v3级别直推人数也正常，则加入集合
                                if ($v3_num >= intval($config['fen2'])) {
                                    $wait[] = ['uid' => $user->id, 'all' => $all];
                                }

                            }
                        }
                    }
                }
            }

            //查询资金池金额
            $pool_amount = Pool::getAmount();

            //有分红人员，且资金池金额大于1
            if (!empty($wait) && $pool_amount >= 1) {

                //总投资金额
                $sum_amount = array_sum(array_column($wait, 'all'));

                //批量计算、发放金额
                foreach ($wait as $n) {

                    $reward = round($n['all'] / $sum_amount, 4) * $sum_amount;

                    //高于0.1才发
                    if ($reward >= 0.1) {
                        //发放到账
                        Users::changeAmount($n['uid'], 3, $reward);

                        //插入资金日志
                        MoneyLog::addLog($user->id,1, $reward,8);
                    }
                }
            }
        }

        return 'success';
    }
}