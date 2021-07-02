<?php

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 基础模型
 * Class TimeModel
 * @package app\common\model
 */
class TimeModel extends Model
{

    /**
     * 自动时间戳类型
     * @var string
     */
    protected $autoWriteTimestamp = true;

    /**
     * 添加时间
     * @var string
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间
     * @var string
     */
    protected $updateTime = 'update_time';

    /**
     * 软删除
     */
    use SoftDelete;
    protected $deleteTime = false;

    /**
     * 资金日志对照
     */
    public static $mtype = [
        1  =>  '质押返利',
        2  =>  '质押投资',
        3  =>  '推荐奖',
        4  =>  '管理奖',
        5  =>  '充值',
        6  =>  '提现',
        7  =>  '提现驳回',
        8  =>  '分红',
        9  =>  '质押到期返还本金',
        10 =>  '提前解押返还本金',
    ];

}