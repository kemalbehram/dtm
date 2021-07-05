<?php

namespace app\index\controller;

use app\admin\model\Orders;
use app\admin\model\Users;
use app\common\controller\AdminController;
use think\App;

class Index extends AdminController
{
    protected $layout = false;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function index()
    {
        $ref = $this->request->param('ref');
        $lang = $this->request->param('lang');
        
        if (!empty($ref)) {
            cookie('ref', $ref);
        }
        
        if ($lang == 'en') {
            session('lang', 'en');
            $template = 'index/index_en';
        }else{
            session('lang', 'zh');
            $template = 'index/index';
        }

        return $this->fetch($template);
    }

    public function order()
    {
        return $this->fetch();
    }

    public function user()
    {
        return $this->fetch();
    }

    public function start()
    {
        $amount = $this->request->param('amount/d');
        $address = $this->request->param('address/s');
        $types = $this->request->param('types/d');
        $token = $this->request->param('token/s');

        $config = sysconfig('other');

        $user = Users::where('address', $address)->find();

        if (empty($user)) $this->error('请先连接钱包');
        if (empty($token) || ($token <> $user->token)) $this->error('Token不正确');
        if ($amount <= 0 || $amount < (float)$config['zy_min']) $this->error('质押数量最低'.$config['zy_min'].'DTM');
        if (!in_array($types, [1,7,15,30])) $this->error('请选择质押期限');

        try {
            Orders::fund($user->id, $types, $amount);
        } catch (\Exception $e) {
            $this->error('质押失败：'.$e->getMessage());
        }

        $this->success('质押成功');
    }

    public function money_log()
    {
        $address = $this->request->param('address/s');

        $uid = Users::address2id($address);

        if (empty($uid)) $this->error('请先连接钱包');

        $data = Orders::where('uid', $uid)->select();

        if ($data->isEmpty()) {
            $this->error('暂无数据');
        }
        $this->success('获取成功', $data);
    }

    //兑换
    public function exchange()
    {
        $amount = $this->request->param('amount');
        $address = $this->request->param('address/s');
        $type = $this->request->param('type/d',1);
        $token = $this->request->param('token/s');

        $config = sysconfig('other');

        $user = Users::where('address', $address)->find();

        $amount = floatval($amount);

        if (empty($user)) $this->error('请先连接钱包');
        if (empty($token) || ($token <> $user->token)) $this->error('Token不正确');
        if ($amount <= 0) $this->error('数量不能低于0');
        if (!in_array($type, [1,2])) $this->error('提交数据出错');

        if ($type == 1) {
            $dtm_num = $amount / (float)$config['dtm_usdt_price'];
            if ($dtm_num < (float)$config['business_deal_min']) $this->error('买入数量不能低于 '.$config['business_deal_min'].' DTM');
        } else {
            if ($amount < (float)$config['business_deal_min']) $this->error('卖出数量不能低于 '.$config['business_deal_min'].' DTM');
        }

        try {
            if ($type == 1) {
                Users::usdt2dtm($user->id, $amount);
            } else {
                Users::dtm2usdt($user->id, $amount);
            }
        } catch (\Exception $e) {
            $this->error('兑换失败：'.$e->getMessage());
        }

        $this->success('兑换成功');
    }

    //提前解押
    public function release()
    {
        try {

            $order_id = $this->request->param('order_id/d');
            $order = Orders::find($order_id);

            Orders::release($order->id);

        } catch (\Exception $e) {
            $this->error('提前解押失败：'.$e->getMessage());
        }

        $this->success('提前解押成功');
    }
}