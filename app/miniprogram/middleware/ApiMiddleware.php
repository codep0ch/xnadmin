<?php
/**
 * +———————————————————————-
 * | Api中间件
 * +———————————————————————-
 */
namespace app\miniprogram\middleware;

use think\Exception;
use think\facade\Request;
use think\Response;
use think\exception\HttpResponseException;

class ApiMiddleware
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    public function handle($request, \Closure $next)
    {
        $strToken = Request::header('token');
        if ($strToken) {
            if (count(explode('.', $strToken)) <> 3) {
                throw new \Exception('非法身份信息,请重新登录', 401);
            }
            //获取JwtAuth的句柄
            $objJwtAuth = JwtBaseService::getInstance();
            $claims = $objJwtAuth->validatorToken($strToken);
            $request->claims = $claims;
            return $next($request);
        } else {
            throw new \Exception('请先登录', 401);
        }
    }
}