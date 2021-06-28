<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use think\App;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="排位关系图")
 */
class Tree extends AdminController
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
        if ($this->request->isAjax()) {
            $data = $this->model->getTree();
            return json($data);
        }
        return $this->fetch();
    }
}