<?php

namespace app\admin\controller;

use app\admin\model\Commonpath;
use app\admin\model\MoneyLog;
use app\admin\model\Pool;
use app\admin\model\Regpath;
use app\common\controller\AdminController;
use think\App;
use think\Exception;
use think\facade\Db;
use app\admin\model\Recharge;
use app\admin\model\Users;
use app\admin\model\Orders;

//定时任务
class Task extends AdminController
{
    //采集BUSD充值记录
    public function task1() {
        $address = sysconfig('other','recharge_address');
        //contractaddress是BUSD合约
        //KeyTokenkey有访问频率限制
        $url = 'https://api.bscscan.com/api?module=account&action=tokentx&contractaddress=0xe9e7cea3dedca5984780bafc599bd69add087d56&address='.$address.'&sort=desc&KeyTokenkey=63XZBH7UM3CCA7UC6N5JK5DYQQFW8R6W7T';
        $result = curl_get($url);
        if (!empty($result['result'])) {
            //待入库集合
            $arr = [];
            foreach ($result['result'] as $v) {
                //非收款记录，抛弃
                if ($v['to'] <> $address) continue;
                $amount = bcdiv($v['value'],1000000000000000000,4);
                //金额不正常的抛弃
                if ($amount <= 0) continue;
                $arr[] = [
                    'tx'            =>  $v['hash'],
                    'symbol'        =>  $v['tokenSymbol'],
                    'from_address'  =>  $v['from'],
                    'to_address'    =>  $v['to'],
                    'amount'        =>  $amount,
                    'block_time'    =>  $v['timeStamp'],
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
        try {
            //筛选所有未处理的数据
            $data = Recharge::where('status', 0)->select();

            //循环处理订单
            foreach ($data as $v) {
                //获取用户资料
                $user = Users::where('address', strtolower($v->from_address))->find();

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

                    //提交数据
                    $user->save();

                    //插入资金日志
                    MoneyLog::addLog($user->id,0, $v->amount,5, $v->id);

                    //充值记录标记为已处理、已入账，更新处理时间
                    $v->status = 1;
                    $v->state = 1;
                    $v->cl_time = time();
                    $v->save();

                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    continue;
                }

                //充值排位
                Recharge::rechargeAddCommonpath($user->id);

            }

        } catch (Exception $e) {

            return 'error：'.$e->getMessage();

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

        return '今日返利订单数：'.$num.'单';

    }

    //手续费资金池每日分红
    public function task4()
    {
        //取得相关配置
        $config = sysconfig('other');

        //计算今日待分红金额
        $award = Pool::getAmount() * (float)$config['fh_sxf_bl'] / 100;

        //如果今日已分红，不在继续
//        if (!Pool::isReward()) return 'already';

        //获取分红统计
        $data = Users::dividends_statistics();

        //满足条件的人员为空
        if (empty($data)) return '今日分红人数：0人';

        //总分红权
        $all_dividend_right = array_sum(array_column($data, 'dividend_right'));

        //计算单份分红权的金额
        $amount = $award / $all_dividend_right;

        foreach ($data as $v) {

            //实际分红金额
            $real_amount = $amount * $v['dividend_right'];

            //资金入账
            Users::changeAmount($v['uid'], 2, $real_amount);

            //插入资金日志
            MoneyLog::addLog($v['uid'],0, $real_amount,8, 0);

            //每成功分红一个人，资金池对应产生消耗
            Pool::decPool($real_amount);

        }

        return 'success';
    }
}