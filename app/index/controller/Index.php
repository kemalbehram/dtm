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
        $type = $this->request->param('type/d');

        $uid = Users::address2id($address);

        if (empty($uid)) $this->error('请先连接钱包');
        if ($amount % 100 <> 0 || $amount <= 0) $this->error('投资金额必须为100的整数倍');
        if (!in_array($type, [1,2,3])) $this->error('请选择付款账户');

        try {
            Orders::fund($uid, $type, $amount);
        } catch (\Exception $e) {
            $this->error('投资失败：'.$e->getMessage());
        }

        $this->success('投资成功');
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
}