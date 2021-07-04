<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class Withdraw extends TimeModel
{

    protected $name = "withdraw";

    protected $deleteTime = false;
    
    public function getStatusList()
    {
        return ['0'=>'待审核','1'=>'已审核',];
    }

    public function getClTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    //获取累计提现
    public static function getAllWithdraw($uid)
    {
        return MoneyLog::where(['mtype' => 6, 'uid' => $uid])->sum('amount') ?? 0;
    }


}