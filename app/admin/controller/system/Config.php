<?php


namespace app\admin\controller\system;


use app\admin\model\SystemConfig;
use app\admin\service\TriggerService;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Config
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="系统配置管理")
 */
class Config extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemConfig();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="保存")
     */
    public function save()
    {
        $post = $this->request->post();
        $rule = [
            'address1|1日地址'     => 'alphaNum',
            'address5|5日地址'     => 'alphaNum',
            'address10|10日地址'   => 'alphaNum',
            'address15|15日地址'   => 'alphaNum',
            'address20|20日地址'   => 'alphaNum',
            'income1|1日收益'      => 'float',
            'income5|5日收益'      => 'float',
            'income10|10日收益'    => 'float',
            'income15|15日收益'    => 'float',
            'income20|20日收益'    => 'float',
            'dynamic1|1代'         => 'float',
            'dynamic5|2代'         => 'float',
            'dynamic10|3代'        => 'float',
            'dynamic15|4-10代'     => 'float',
            'dynamic20|11-20代'    => 'float',
        ];
        $this->validate($post, $rule);

        try {
            foreach ($post as $key => $val) {
                $this->model
                    ->where('name', $key)
                    ->update([
                        'value' => $val,
                    ]);
            }
            TriggerService::updateMenu();
            TriggerService::updateSysconfig();
        } catch (\Exception $e) {
            $this->error('保存失败');
        }
        $this->success('保存成功');
    }

}