<?php

namespace app\admin\controller;

use app\admin\model\MoneyLog;
use app\admin\model\Regpath;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use think\App;
use think\facade\Db;
use app\admin\model\Users;

/**
 * @ControllerAnnotation(title="投资记录")
 */
class Orders extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Orders();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
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
//    public function delete()
//    {
//        $id = $this->request->param('id');
//        $row = $this->model->whereIn('id', $id)->select();
//        $row->isEmpty() && $this->error('数据不存在');
//        try {
//            $save = $row->delete();
//        } catch (\Exception $e) {
//            $this->error('删除失败');
//        }
//        $save ? $this->success('删除成功') : $this->error('删除失败');
//    }
    
}