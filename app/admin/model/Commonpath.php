<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;

class Commonpath extends TimeModel
{

    protected $name = "commonpath";

    //处理公排关系
    public static function addCommonpath(int $uid, int $fid)
    {
        try {
            //如果没有上级，就是系统第一人，不处理公排关系
            if (empty($fid)) return false;

            //查询上级第一层节点情况
            $f_level1_uids = self::getLevel1($fid);

            //上级第一层节点数量
            $f_level1_num = count($f_level1_uids);

            //如果上级没有第一层节点，就直接排到他下面，并挂载到位置1
            if ($f_level1_num == 0) {

                self::addPath($uid, $fid, $fid,1);

            } else if ($f_level1_num == 1) {

                //如果上级有一个第一层节点，取上级的位置1节点数量
                $subordinates = self::getAllSubordinatesPosition1($fid);

                //查询这些位置1节点中，是否有上级自己推荐的
                $is_me = self::getPosition1ForMe($subordinates, $fid);

                //取上级的叶子节点
                $leaf = self::getLast($fid);

                //如果没有位置1节点，那么就放到叶子节点下，并挂载成位置1
                if (empty($subordinates) || !$is_me) {

                    self::addPath($uid, $leaf, $fid, 1);

                } else {

                    //否则直接挂载到上级的位置2
                    self::addPath($uid, $fid, $fid, 2);

                }
            } else {

                //否则，查询上级的AB线业绩（也就是他的2个子节点各自的团体业绩）
                $performances = self::getAbPerformance($fid);

                //如果业绩正常
                if (!empty($performances)) {

                    //默认选定A线
                    $rank_uid = $performances['a_uid'];
                    //默认选定A线的叶子节点
                    $last_uid = !empty($performances['a_position1']) ? max($performances['a_position1']) : $rank_uid;

                    //判断AB线业绩是否相等，相等就轮流排
                    if ($performances['a_performance'] == $performances['b_performance']) {

//                        print_r($performances);exit;

                        //轮流排，优先选择还没排的线
                        if ($performances['last_group'] == 2) {
                            $rank_uid = $performances['b_uid'];
                            $last_uid = !empty($performances['b_position1']) ? max($performances['b_position1']) : $rank_uid;
                        }

                    } else {

                        //如果业绩不等，就取小区
                        if ($performances['a_performance'] > $performances['b_performance']) {
                            $rank_uid = $performances['b_uid'];
                            $last_uid = !empty($performances['b_position1']) ? max($performances['b_position1']) : $rank_uid;
                        }

                    }

                    //选举情况调试
//                     var_dump($rank_uid);exit;

                    //叶子节点
//                    var_dump($last_uid);exit;

                    self::addPath($uid, $last_uid, $fid, 1);

                }
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        return false;
    }

    //查询某账号的一级节点(排序)
    public static function getLevel1(int $uid)
    {
        $arr = self::where(['uid' => $uid, 'level' => 1])->order('id','asc')->column('member_uid');
        return array_values($arr);
    }

    //查询某账号的末级
    //最大level可能有2个叶子节点，取ID最大即可
    public static function getLast(int $uid)
    {
        return self::where('uid', $uid)->order(['level'=>'desc','id'=>'desc'])->limit(1)->value('member_uid');
    }

    //查询某账号的所有公排下级
    public static function getAllSubordinates(int $uid)
    {
        $arr = self::where('uid', $uid)->column('member_uid');
        return array_values($arr);
    }

    //递归查询某账号的所有位置为1的一串公排下级
    public static function getAllSubordinatesPosition1(int $uid)
    {
        $arr = [];
        $stop = true;
        do {
            $position1 = self::where(['uid' => $uid, 'level' => 1, 'position' => 1])->value('member_uid');
            if (empty($position1)) {
                $stop = false;
            } else {
                $arr[] = $position1;
                $uid = $position1;
            }
        } while($stop);
        return $arr;
    }

    //查询位置1节点组里面，是否有自己推荐的
    public static function getPosition1ForMe(array $position1, int $uid) {
        if (empty($position1)) {
            return false;
        }
        return self::whereIn('member_uid', $position1)->where('referrer', $uid)->count() ? true : false;
    }

    //查询某账号的公排上级
    public static function getFuid($uid)
    {
        return self::where(['member_uid' => $uid, 'level' => 1])->value('uid');
    }


    //查询某账号AB线业绩
    public static function getAbPerformance(int $uid):array
    {
        //获取他的一级节点
        $level1_uids = self::getLevel1($uid);

//        var_dump($level1_uids);exit;

        //万一有2个以上，只取2个
        if (count($level1_uids) > 2) {
            $level1_uids = array_slice($level1_uids, 1);
        }

        //万一小于2个，退出
        if (count($level1_uids) < 1) {
            return [];
        }

        //万一只有A线
        if (count($level1_uids) == 1) {
            //A线所有下级，包括自己
            $a_subordinates = self::getAllSubordinates($level1_uids[0]);

            //A线所有位置1下级，包括自己
            $a_position1_subordinates = self::getAllSubordinatesPosition1($level1_uids[0]);

            //A线所有属于自己的位置1下级个数
            $a_position1_subordinates_me = self::whereIn('member_uid', $a_position1_subordinates)->where('referrer', $uid)->count();

            //A线团队业绩，包括A自己
            $a_performance = Orders::getTeamPerformance($a_subordinates) + Orders::cumulative_investment($level1_uids[0]);

            $arr =  [
                'a_uid'             =>  $level1_uids[0],
                'a_performance'     =>  $a_performance,
                'a_num'             =>  count($a_subordinates),
                'a_position1'       =>  $a_position1_subordinates,
                'b_uid'             =>  0,
                'b_performance'     =>  0,
                'b_num'             =>  0,
                'b_position1'       =>  0,
                'last_group'        =>  $a_position1_subordinates_me > 1 ? 2 : 1,
            ];

            return $arr;
        }

        //A线所有下级，包括自己
        $a_subordinates = self::getAllSubordinates($level1_uids[0]);

        //B线所有下级，包括自己
        $b_subordinates = self::getAllSubordinates($level1_uids[1]);

        //A线所有位置1下级，包括自己
        $a_position1_subordinates = self::getAllSubordinatesPosition1($level1_uids[0]);

        //A线所有属于自己的位置1下级个数
        $a_position1_subordinates_me = self::whereIn('member_uid', $a_position1_subordinates)->where('referrer', $uid)->count();

        //B线所有位置1下级
        $b_position1_subordinates = self::getAllSubordinatesPosition1($level1_uids[1]);

        //B线所有属于自己的位置1下级个数，包括自己
        $b_position1_subordinates_me = self::whereIn('member_uid', $b_position1_subordinates)->where('referrer', $uid)->count() + 1;

        //A线团队业绩，包括A自己
        $a_performance = Orders::getTeamPerformance($a_subordinates) + Orders::cumulative_investment($level1_uids[0]);

        //B线团队业绩，包括B自己
        $b_performance = Orders::getTeamPerformance($b_subordinates) + Orders::cumulative_investment($level1_uids[1]);

        $arr =  [
            'a_uid'             =>  $level1_uids[0],
            'a_performance'     =>  $a_performance,
            'a_num'             =>  count($a_subordinates),
            'a_position1'       =>  $a_position1_subordinates,
            'b_uid'             =>  $level1_uids[1],
            'b_performance'     =>  $b_performance,
            'b_num'             =>  count($b_subordinates),
            'b_position1'       =>  $b_position1_subordinates,
            'last_group'        =>  $a_position1_subordinates_me > $b_position1_subordinates_me ? 2 : 1,
        ];

        return $arr;
    }

    //关系入库
    public static function addPath(int $uid, int $fid, int $referrer, int $position = 0):bool
    {

        //先登记公排上级
        Users::where('id', $uid)->update([
            'cid'   =>  $fid,
        ]);

        if (!empty($fid)) {

            //查询上级的注册关系
            $data = self::where('member_uid', $fid)->field('uid,member_uid,level')->select();

            //根据上级的关系，设置我的注册关系
            foreach ($data as &$v) {
                $v['member_uid']    =  $uid;
                $v['level']         += 1;
                $v['create_time']   =  time();
                //无关紧要的人 位置为0
                $v['position']      =  0;
                //无关紧要的人 推荐人为0
                $v['referrer']      =  0;
            }

            //追加我与上级的关系
            $data[] = [
                'uid'               =>  $fid,
                'member_uid'        =>  $uid,
                'level'             =>  1,
                'create_time'       =>  time(),
                //我是上级的 左节点 or 右节点
                'position'          =>  $position,
                //我的推荐人
                'referrer'          =>  $referrer,
            ];

            //入库
            Db::name('commonpath')->extra('IGNORE')->insertAll($data->toArray());

            return true;
        }
        return false;
    }

    //公排tree树
    public static function getTree():array
    {
        $list = Users::field('id,cid,address as title')->select();
        foreach ($list as &$v) {
            //AB线
            $ab_performance = Commonpath::getAbPerformance($v->id);
            $a_performance = $ab_performance['a_performance'] ?? 0;
            $b_performance = $ab_performance['b_performance'] ?? 0;

            //管理奖等级
            $level = Users::management_level($v['id'])['level'] ?? '无';

            $v->title .= '（ID：'.$v->id.'，A线：'.$a_performance.'，B线：'.$b_performance.'，等级：'.$level.'）';
        }
        $tree = list2tree($list->toArray(),'id','cid');
        return $tree;
    }
}