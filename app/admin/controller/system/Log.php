<?php

namespace app\admin\controller\system;


use app\admin\model\SystemLog;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="操作日志管理")
 * Class Auth
 * @package app\admin\controller\system
 */
class Log extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemLog();
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
            [$page, $limit, $where, $excludeFields] = $this->buildTableParames(['month']);

            $month = (isset($excludeFields['month']) && !empty($excludeFields['month']))
                ? date('Ym',strtotime($excludeFields['month']))
                : date('Ym');
            $count = $this->model
                ->setMonth($month)
                ->with('admin')
                ->where($where)
                ->select();
            $list = $this->model
                ->setMonth($month)
                ->with('admin')
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

} 
