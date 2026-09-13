<?php
declare(strict_types=1);

namespace app\middleware;

use app\common\model\AdminUser;
use Closure;
use think\Request;
use think\Response;

/**
 * 后台登录鉴权
 * 参数 role： 1=仅管理员， 2=老板或管理员均可（只读看板）
 */
class Auth
{
    public function handle(Request $request, Closure $next, int $role = 0): Response
    {
        $uid = session('user.id');
        if (!$uid) {
            if ($request->isAjax()) {
                return json(['code' => 401, 'msg' => '请先登录'], 401);
            }
            return redirect((string) url('/login'));
        }

        $userRole = (int) (session('user.role') ?? 0);

        // role=1：管理员专区；老板(2)不允许进入
        if ($role === AdminUser::ROLE_ADMIN && $userRole !== AdminUser::ROLE_ADMIN) {
            return Response::create('无权访问该页面（仅管理员）', 'html', 403);
        }

        // role=2：老板看板，老板与管理员都可访问
        if ($role === AdminUser::ROLE_BOSS
            && !in_array($userRole, [AdminUser::ROLE_ADMIN, AdminUser::ROLE_BOSS], true)) {
            return Response::create('无权访问', 'html', 403);
        }

        return $next($request);
    }
}
