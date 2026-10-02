<?php
declare(strict_types=1);

namespace app\middleware;

use app\model\User;
use think\facade\Session;
use think\facade\View;
use think\Request;
use think\Response;

/**
 * 登录鉴权基类
 * 子类通过 $needRole 限定可访问角色
 */
abstract class Auth
{
    protected ?int $needRole = null;

    public function handle(Request $request, \Closure $next): Response
    {
        $uid = Session::get('user_id');
        if (!$uid) {
            return $this->redirectLogin($request);
        }

        $user = User::find($uid);
        if (!$user || (int)$user->status !== 1) {
            Session::clear();
            return $this->redirectLogin($request);
        }

        if ($this->needRole !== null && (int)$user->role !== $this->needRole) {
            return Response::create('无权访问该页面 (403)', 'html', 403);
        }

        $request->user = $user;
        View::assign('current_user', $user);

        return $next($request);
    }

    protected function redirectLogin(Request $request): Response
    {
        $path = $request->pathinfo();
        if (str_starts_with($path, 'scan') || str_starts_with($path, 'employee')) {
            return redirect('/login?back=' . urlencode($request->url()));
        }
        return redirect('/login');
    }
}
