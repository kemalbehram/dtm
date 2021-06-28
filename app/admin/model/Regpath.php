<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\facade\Db;

class Regpath extends TimeModel
{

    protected $name = "regpath";

    //处理注册关系
    public static function addRegpath($uid, $fid)
    {
        if (!empty($fid)) {
            //查询上级的注册关系
            $data = self::where('member_uid', $fid)->field('uid,member_uid,level')->select();
            //根据上级的关系，设置我的注册关系
            foreach($data as &$v){
                $v['member_uid']    = $uid;
                $v['level']         += 1;
                $v['create_time']   =  time();
            }
            //追加我与上级的关系
            $data[] = [
                'uid'               =>  $fid,
                'member_uid'        =>  $uid,
                'level'             =>  1,
                'create_time'       =>  time(),
            ];
            //入库
            Db::name('regpath')->extra('IGNORE')->insertAll($data->toArray());
        }
        return true;
    }

    //注册关系tree树
    public static function getTree()
    {
        $list = Users::field('id,fid,address as title')->select();
        foreach ($list as &$v) {
            $v->title .= '（ID：'.$v->id.'）';
        }
        $tree = list2tree($list->toArray(),'id','fid');
        return $tree;
    }

    //查询某会员的某级下线人员
    public static function getLevelUids($uid, $level)
    {
        return self::where(['uid' => $uid, 'level' => $level])->column('member_uid');
    }
}