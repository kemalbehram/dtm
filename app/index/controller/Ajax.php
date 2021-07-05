<?php

namespace app\index\controller;

use app\admin\model\Commonpath;
use app\admin\model\MoneyLog;
use app\admin\model\Orders;
use app\admin\model\Recharge;
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
            'address|钱包地址'     => 'require|alphaNum|length:42',
        ];
        $message = [
            'address.require'   =>  '请连接钱包',
            'address.alphaNum'  =>  '请连接钱包',
            'address.length'    =>  '请连接钱包',
        ];
        $this->validate($get, $rule, $message);

        try {
            $config = sysconfig('other');

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
            $user->zy_award = $award['zy_award'];
            $user->tj_award = $award['tj_award'];
            $user->sy_award = $award['sy_award'];
            $user->fh_award = $award['fh_award'];
            $user->all_award = $award['all_award'];

            //累计充值、累计提现
            $user->all_recharge = Recharge::getAllRecharge($user->id);
            $user->all_withdraw = Withdraw::getAllWithdraw($user->id);

            //推广人数
            $user->share_num = Regpath::where(['uid' => $user->id, 'level' => 1])->count();

            //邀请链接
            $user->invite_url = request()->domain().'/?ref='.$user->address;

            //是否累计充值60U+
            $user->isRecharge60 = (Recharge::getAllRecharge($user->id) < $config['tg_recharge']) ? false : true;

            //USDT兑换DTM手续费
            $user->buy_fee = (float)$config['buy_fee'];

            //自动质押比例
            $user->auto_buy_bl = (float)$config['auto_buy_bl'];

            //DTM/USDT价格
            $user->dtm_usdt_price = (float)$config['dtm_usdt_price'];

            //DTM兑换USDT手续费
            $user->sell_fee = Users::getSellFee($user->id);

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：'.$e->getMessage()]);
        }

        return json(['code' => 1, 'msg' => '获取成功', 'data' => $user]);
    }

    //USDT提现
    public function withdraw()
    {
        $get = $this->request->param();
        $rule = [
            'address|钱包地址'     => 'require|alphaNum|length:42',
            'num|提现数量'         => 'require|number',
            'token|Token'          => 'require',
        ];
        $this->validate($get, $rule);

        $user = Users::where('address', $get['address'])->field('id,address,amount1')->find();

        if (empty($user)) {
            $this->error('请连接钱包');
        }
        if ($user->token <> $get['token']) {
            $this->error('Token不正确');
        }
        if ($user->amount1 <= 0 || $user->amount1 < $get['num']) {
            $this->error('USDT余额不足');
        }

        Db::startTrans();
        try {
            $config = sysconfig('other');

            $result = Withdraw::create([
               'uid'            =>  $user->id,
               'amount'         =>  $get['num'],
               'fee'            =>  $get['num'] * (float)$config['withdraw_fee'] / 100,
               'real_amount'    =>  $get['num'] * (1 - (float)$config['withdraw_fee'] / 100),
            ]);

            $user->amount1 -= $get['num'];
            $user->save();

            MoneyLog::addLog($user->id, 1, -$user->amount1, 6, $result->id);

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
            'address|钱包地址'     => 'require|alphaNum|length:42',
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

    //获取最近15条资金日志
    public function getMoneyLog()
    {
        $get = $this->request->param();
        $rule = [
            'address|钱包地址'     => 'require|alphaNum|length:42',
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