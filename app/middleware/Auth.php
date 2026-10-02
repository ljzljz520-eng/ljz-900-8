<?php
declare (strict_types = 1);

namespace app\middleware;

use think\facade\Session;

/**
 * 登录与角色校验中间件
 * 用法：->middleware(\app\middleware\Auth::class, 'admin')
 */
class Auth
{
    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure       $next
     * @param string         $role 需要的角色：admin / staff / boss，空表示仅需登录
     * @return \think\Response
     */
    public function handle($request, \Closure $next, string $role = '')
    {
        $user = Session::get('user');

        if (!$user) {
            // 记录来源地址（含query），登录后原路跳回，保证扫码直达
            $url   = $request->url();
            $query = (string) $request->server('QUERY_STRING', '');
            if ($query !== '') {
                $url .= '?' . $query;
            }
            Session::set('error', '请先登录');
            return redirect('/login?redirect=' . urlencode($url));
        }

        if ($role !== '' && ($user['role'] ?? '') !== $role) {
            Session::set('error', '没有访问权限');
            return redirect('/');
        }

        return $next($request);
    }
}
