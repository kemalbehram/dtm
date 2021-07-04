<?php

namespace app\admin\controller;

use app\admin\model\MoneyLog;
use app\admin\model\Pool;
use app\admin\model\Regpath;
use app\admin\model\Users;
use app\common\controller\AdminController;
use app\common\model\TimeModel;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use think\App;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="提现管理")
 */
class Withdraw extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Withdraw();
        
        $this->assign('getStatusList', $this->model->getStatusList());

    }

    /**
     * @NodeAnotation(title="待审核列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['status', '=', 0];

            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            foreach ($list as &$v) {
                $v['address'] = Users::id2address($v->uid);
                $v['fee'] = $v->amount * $v->fee / 100;
                $v['real_amount'] = $v->amount - $v['fee'];
            }
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="已审核列表")
     */
    public function index2()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['status', '=', 1];

            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            foreach ($list as &$v) {
                $v['address'] = Users::id2address($v->uid);
            }
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="通过")
     */
    public function arrive()
    {
        $id = $this->request->param('id/d');
        $data = $this->model->find($id);
        empty($data) && $this->error('数据不存在');
        $this->model->startTrans();
        try {
            $data->status = 1;
            $data->cl_time = time();
            $data->content = '通过';
            $data->save();

            $this->model->commit();
        }catch (\Exception $e) {
            $this->model->rollback();
            $this->error('处理失败：'.$e->getMessage());
        }
        $this->success('处理成功');
    }

    /**
     * @NodeAnotation(title="驳回")
     */
    public function reject()
    {
        $id = $this->request->param('id/d');
        $data = $this->model->find($id);
        empty($data) && $this->error('数据不存在');
        $this->model->startTrans();
        try {
            $data->status = 1;
            $data->cl_time = time();
            $data->content = '驳回';
            $data->save();

            Users::where('id', $data->uid)->update([
               'amount1' =>  Db::raw('amount1+'.($data->amount + $data->fee)),
            ]);

            MoneyLog::addLog($data->uid, 0, $data->amount + $data->fee, 2, $id);

            $this->model->commit();
        }catch (\Exception $e) {
            $this->model->rollback();
            $this->error('驳回失败：'.$e->getMessage());
        }
        $this->success('驳回成功');
    }

    /**
     * @NodeAnotation(title="导出")
     */
    public function export()
    {
        list($page, $limit, $where) = $this->buildTableParames();
        $tableName = $this->model->getName();
        $tableName = CommonTool::humpToLine(lcfirst($tableName));
        $prefix = config('database.connections.mysql.prefix');
        $dbList = Db::query("show full columns from {$prefix}{$tableName}");
        $header = [];
        foreach ($dbList as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }
        $list = $this->model
            ->where($where)
            ->limit(100000)
            ->order($this->sort)
            ->select()
            ->toArray();
        $fileName = time();
        return Excel::exportData($list, $header, $fileName, 'xlsx');
    }
    
    /**
     * @NodeAnotation(title="删除")
     */
    public function delete()
    {
        $row = $this->model->whereIn($this->model->getPk(), $this->request->param($this->model->getPk()))->select();
        $row->isEmpty() && $this->error('数据不存在');
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    
}