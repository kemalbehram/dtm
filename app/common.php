<?php
// 应用公共文件

use app\common\service\AuthService;
use think\exception\HttpResponseException;
use think\facade\Cache;
use Firebase\JWT\JWT;
use app\admin\model\Regpath;
use app\admin\model\Orders;
use app\admin\model\Users;

if (!function_exists('__url')) {

    /**
     * 构建URL地址
     * @param string $url
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return string
     */
    function __url(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return url($url, $vars, $suffix, $domain)->build();
    }
}

if (!function_exists('password')) {

    /**
     * 密码加密算法
     * @param $value 需要加密的值
     * @param $type  加密类型，默认为md5 （md5, hash）
     * @return mixed
     */
    function password($value)
    {
        $value = sha1('blog_') . md5($value) . md5('_encrypt') . sha1($value);
        return sha1($value);
    }

}

if (!function_exists('xdebug')) {

    /**
     * debug调试
     * @param string|array $data 打印信息
     * @param string $type 类型
     * @param string $suffix 文件后缀名
     * @param bool $force
     * @param null $file
     */
    function xdebug($data, $type = 'xdebug', $suffix = null, $force = false, $file = null)
    {
        !is_dir(runtime_path() . 'xdebug/') && mkdir(runtime_path() . 'xdebug/');
        if (is_null($file)) {
            $file = is_null($suffix) ? runtime_path() . 'xdebug/' . date('Ymd') . '.txt' : runtime_path() . 'xdebug/' . date('Ymd') . "_{$suffix}" . '.txt';
        }
        file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] " . "========================= {$type} ===========================" . PHP_EOL, FILE_APPEND);
        $str = (is_string($data) ? $data : ((is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true))) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}

if (!function_exists('sysconfig')) {

    /**
     * 获取系统配置信息
     * @param $group
     * @param null $name
     * @return array|mixed
     */
    function sysconfig($group, $name = null)
    {
        $where = ['group' => $group];
        $value = empty($name) ? Cache::get("sysconfig_{$group}") : Cache::get("sysconfig_{$group}_{$name}");
        if (empty($value)) {
            if (!empty($name)) {
                $where['name'] = $name;
                $value = \app\admin\model\SystemConfig::where($where)->value('value');
                Cache::tag('sysconfig')->set("sysconfig_{$group}_{$name}", $value, 3600);
            } else {
                $value = \app\admin\model\SystemConfig::where($where)->column('value', 'name');
                Cache::tag('sysconfig')->set("sysconfig_{$group}", $value, 3600);
            }
        }
        return $value;
    }
}

if (!function_exists('array_format_key')) {

    /**
     * 二位数组重新组合数据
     * @param $array
     * @param $key
     * @return array
     */
    function array_format_key($array, $key)
    {
        $newArray = [];
        foreach ($array as $vo) {
            $newArray[$vo[$key]] = $vo;
        }
        return $newArray;
    }

}

if (!function_exists('auth')) {

    /**
     * auth权限验证
     * @param $node
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function auth($node = null)
    {
        $authService = new AuthService(session('admin.id'));
        $check = $authService->checkNode($node);
        return $check;
    }
}

if (!function_exists('whereHook')) {
    /**
     * 强行非关联搜索where拦截器
     */
    function whereHook($data, $pk1, $model, $pk2)
    {
        switch (strtolower($data[1])) {
            case '=':
                $where[] = [$data[0], '=', $data[2]];
                break;
            case '%*%':
                $where[] = [$data[0], 'LIKE', "%{$data[2]}%"];
                break;
            case '*%':
                $where[] = [$data[0], 'LIKE', "{$data[2]}%"];
                break;
            case '%*':
                $where[] = [$data[0], 'LIKE', "%{$data[2]}"];
                break;
            case 'range':
                [$beginTime, $endTime] = explode(' - ', $data[2]);
                $where[] = [$data[0], '>=', strtotime($beginTime)];
                $where[] = [$data[0], '<=', strtotime($endTime)];
                break;
            default:
                $where[] = [$data[0], $data[1], "%{$data[2]}"];
        }
        $res = $model::where($where)->column($pk2);
        return [$pk1, 'in', $res];
    }
}

function result($code = 1, $msg = '', $data = [])
{
    return [
        'code'  => $code,
        'msg'   => $msg,
        'data'  => $data,
    ];
}

function curl_get($url, $header=[])
{
    $ch = curl_init();
    if(!empty($header)){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output, true);
    return $output;
}

if (!function_exists('list2tree')) {
    /**
     * 树目录输出
     */
    function list2tree(array $list, string $pk = 'id', string $sid = 'pid', string $child = 'children', int $root = 0, int $status = 0)
    {
        $tree = [];
        if (is_array($list)) {
            $refer = [];
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                $parentid = $data[$sid];
                if ($parentid == $root) {
                    unset($list[$key]['pid']);
                    $list[$key]['open'] = true;
                    $list[$key]['checked'] = false;
                    $list[$key]['spread'] = true;
                    $tree[] =& $list[$key];
                } else {
                    if (isset($refer[$parentid])) {
                        $parent =& $refer[$parentid];
                        unset($list[$key]['pid']);
                        $list[$key]['open'] = true;
                        $list[$key]['checked'] = false;
                        $list[$key]['spread'] = true;
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }
}