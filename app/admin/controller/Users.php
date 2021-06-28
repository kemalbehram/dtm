<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\admin\model\Regpath;
use app\admin\model\Orders;

/**
 * @ControllerAnnotation(title="用户管理")
 */
class Users extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Users();
        
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
            foreach ($list as &$v) {
                $v['fl_time'] = !empty($v['fl_time']) ? date('Y-m-d H:i:s', $v['fl_time']) : '无';
                //管理奖等级
                $v['level'] = \app\admin\model\Users::management_level($v['id'])['level'] ?? '无';
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

    
}