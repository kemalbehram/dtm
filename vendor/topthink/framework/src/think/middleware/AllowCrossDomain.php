<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 跨域请求支持
 */
class AllowCrossDomain
{
    protected $header = [
        'Access-Control-Allow-Headers'     => '*',
        'Access-Control-Allow-Origin'      => '*',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
    ];

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param array   $header
     * @return Response
     */
    public function handle($request, Closure $next, ?array $header = [])
    {
        $header = !empty($header) ? array_merge($this->header, $header) : $this->header;

        if ($request->method(true) == 'OPTIONS') {
            return Response::create()->code(204)->header($header);
        }

        return $next($request)->header($header);
    }
}
