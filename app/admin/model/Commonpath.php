<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class Commonpath extends TimeModel
{

    protected $name = "commonpath";

    //处理公排关系
    public static function addCommonpath(int $uid, int $fid)
    {
        try {

            //如果没有上级，不处理公排关系
            if (empty($fid)) return false;

            //查询上级第一层节点情况
            $f_level1_uids = self::getLevel1($fid);

            //上级第一层节点数量
            $f_level1_uids_num = count($f_level1_uids);

            //如果上级的第一层节点数量小于3，就直接排到他下面相应位置
            if ($f_level1_uids_num < 3) {

                //位置 = 节点数 + 1
                self::addPath($uid, $fid, $fid,$f_level1_uids_num + 1);

            } else {

                //否则查找上级的末级叶子节点
                $last_id = self::getLast($fid);

                //查询末级叶子节点的上级
                $last_fid = self::getFuid($last_id);

                //查询末级叶子节点的上级的第一层节点情况
                $last_fid_child = self::getLevel1($last_fid);

//                var_dump($last_fid_child);exit;

                //末级叶子节点的上级的第一层节点数量
                $last_fid_child_count = count($last_fid_child);

                //如果末级叶子节点的上级的第一层节点数量小于3，排到末级叶子节点的上级的相应位置即可
                if ($last_fid_child_count < 3) {

                    self::addPath($uid, $last_fid, $fid,$last_fid_child_count + 1);

                } else {

                    //否则要看看末级叶子节点的上级在他自己这一层的位置

                    //如果末级叶子节点的上级就是上级
                    if ($last_fid == $fid) {

                        //排到上级第一个元素下面
                        self::addPath($uid, $last_fid_child[0], $fid,1);
                        return true;

                    }

                    //查询末级叶子节点的上级相对于fid的层级
                    $last_fid_level = self::where(['uid' => $fid, 'member_uid' => $last_fid])->value('level');

                    //查询fid在该层的所有成员
                    $fid_level_child = self::where(['uid' => $fid, 'level' => $last_fid_level])->order('id','desc')->column('member_uid');
                    $fid_level_child = array_values($fid_level_child);

//                    var_dump($fid_level_child);
//                    var_dump($last_fid);

                    //查询末级叶子节点的上级在这一层的位置
                    $last_fid_position = array_search($last_fid, $fid_level_child);

                    //如果这个位置存在
                    if ($last_fid_position !== false) {

//                        var_dump($last_fid_position.'-'.(count($fid_level_child) - 1));exit;

                        //如果位置不在该层的末尾
                        if ($last_fid_position <> count($fid_level_child) - 1) {

                            //取得该位置的下一个位置成员id
                            $next_id = $fid_level_child[$last_fid_position + 1];

                            //排在这个id下面, 且其位置肯定为1（没有子元素的新节点）
                            self::addPath($uid, $next_id, $fid,1);

                        } else {

                            //如果在该层末尾，需要排到该层第一个元素的第一个子元素下面, 且其位置也肯定为1
                            $first_child = self::getLevel1($fid_level_child[0]);
                            var_dump($first_child);exit;
                            self::addPath($uid, $first_child[0], $fid,1);

                        }

                    } else {

                        //找不到位置，记录个日志
                        throw new Exception('找不到'.$last_fid.'在'.$fid.'的第'.$last_fid_level.'层中的位置');

                    }

                }

            }

        } catch (\Exception $e) {

            //日志记录
            Log::write($e->getMessage());

            //向外层抛出错误
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

    //查询某账号的排位末级
    //最大level可能有3个叶子节点，取ID最大即可
    public static function getLast(int $uid)
    {
        return self::where('uid', $uid)->order('id','desc')->limit(1)->value('member_uid');
    }

    //查询某账号的所有公排下级
    public static function getAllSubordinates(int $uid)
    {
        $arr = self::where('uid', $uid)->column('member_uid');
        return array_values($arr);
    }

    //查询某账号的公排上级
    public static function getFuid($uid)
    {
        return self::where(['member_uid' => $uid, 'level' => 1])->value('uid');
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
                //我是上级的 1、2、3位置
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
            $v->title .= '（ID：'.$v->id.'）';
        }
        $tree = list2tree($list->toArray(),'id','cid');
        return $tree;
    }
}