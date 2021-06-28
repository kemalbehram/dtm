<?php

namespace app\index\controller;

use app\admin\model\Commonpath;
use app\admin\model\MoneyLog;
use app\admin\model\Orders;
use app\admin\model\Regpath;
use app\admin\model\SystemUploadfile;
use app\admin\model\Withdraw;
use app\common\controller\AdminController;
use app\common\model\TimeModel;
use app\common\service\MenuService;
use EasyAdmin\upload\Uploadfile;
use think\db\Query;
use think\Exception;
use think\facade\Cache;
use app\admin\model\Users;
use think\facade\Db;

class Ajax extends AdminController
{

    //获取用户信息，不存在则创建用户
    public function getUserInfo()
    {
        $get = $this->request->param();
        $rule = [
            'address|钱包地址'     => 'require|alphaNum|length:34',
        ];
        $message = [
            'address.require'   =>  '请连接钱包',
            'address.alphaNum'  =>  '请连接钱包',
            'address.length'    =>  '请连接钱包',
        ];
        $this->validate($get, $rule, $message);

        try {
            $user = Users::where('address', $get['address'])->find();
            if (empty($user)) {
                $faddress = cookie('ref');
                if ($get['address'] == $faddress) {
                    throw new Exception('推荐人不能为自己');
                }
                $user = Users::userCreate($get['address'], $faddress);
            }

            //收益统计
            $award = MoneyLog::getAward($user->id);
            $user->static_award = $award['static_award'];
            $user->direct_award = $award['direct_award'];
            $user->manage_award = $award['manage_award'];
            $user->all_award = $award['all_award'];

            //分享人数
            $user->share_num = Regpath::where(['uid' => $user->id, 'level' => 1])->count();

            //团队人数
//            $team_uids = Commonpath::where('uid', $user->id)->column('member_uid');
//            $user->team_num = count($team_uids);

            //脑残客户说改成和分享人数一样
            $user->team_num = $user->share_num;

            //团队业绩,AB线
//            $user->team_order_amount = Orders::whereIn('uid', $team_uids)->sum('amount');
            $ab_performance = Commonpath::getAbPerformance($user->id);
            $user->a_performance = $ab_performance['a_performance'] ?? 0;
            $user->b_performance = $ab_performance['b_performance'] ?? 0;

            //邀请链接
            $user->invite_url = request()->domain().'/?ref='.$user->address;

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：'.$e->getMessage()]);
        }

        return json(['code' => 1, 'msg' => '获取成功', 'data' => $user]);
    }

    //推广收益提现
    public function withdraw()
    {
        $get = $this->request->param();
        $rule = [
            'address|钱包地址'     => 'require|alphaNum|length:34',
        ];
        $this->validate($get, $rule);

        $user = Users::where('address', $get['address'])->field('id,address,amount3')->find();
        if (empty($user)) {
            $this->error('钱包地址不存在');
        }
        if ($user->amount3 == 0) {
            $this->error('奖金账户余额不足');
        }
        Db::startTrans();
        try {
            $result = Withdraw::create([
               'uid'            =>  $user->id,
               'amount'         =>  $user->amount3,
               'fee'            =>  $user->amount3 * sysconfig('other','withdraw_fee') / 100,
               'real_amount'    =>  $user->amount3 * (1 - sysconfig('other','withdraw_fee') / 100),
            ]);

            $user->amount3 = 0;
            $user->save();

            MoneyLog::addLog($user->id, 1, -$user->amount3, 6, $result->id);

            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            $this->error('申请失败：'.$e->getMessage());
        }
        $this->success('申请成功, 等待审核！');
    }

    public function getOrder()
    {
        $get = $this->request->param();
        $rule = [
            'address|钱包地址'     => 'require|alphaNum|length:34',
        ];
        $message = [
            'address.require'   =>  '请连接钱包',
            'address.alphaNum'  =>  '请连接钱包',
            'address.length'    =>  '请连接钱包',
        ];
        $this->validate($get, $rule, $message);

        $data = Orders::where('address', $get['address'])->select();
        $this->success('获取成功', $data);
    }

    //获取最近15条收益记录
    public function getMoneyLog()
    {
        $get = $this->request->param();
        $rule = [
            'address|钱包地址'     => 'require|alphaNum|length:34',
        ];
        $message = [
            'address.require'   =>  '请连接钱包',
            'address.alphaNum'  =>  '请连接钱包',
            'address.length'    =>  '请连接钱包',
        ];
        $this->validate($get, $rule, $message);

        $uid = Users::address2id($get['address']);

        empty($uid) && $this->error('钱包地址不存在');

        $data = MoneyLog::where('uid', $uid)->order('id','desc')->limit(15)->select();
        $this->success('获取成功', $data);
    }
}