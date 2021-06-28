<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\TimeModel;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="排位关系图")
 */
class Commonpath extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\Commonpath();

    }

    /**
     * @NodeAnotation(title="首页")
     */
    public function index()
    {
		$data = $this->model->where('level', 1)->field('uid as pid,member_uid as id')->select();
		
		$data = $data->toArray();
		halt($data);
		
		$tree = list2tree($data);
		halt($tree);
		
        return $this->fetch();
    }
}