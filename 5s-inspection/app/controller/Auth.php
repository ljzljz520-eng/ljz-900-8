<?php
declare(strict_types=1);

namespace app\controller;

use app\model\User;
use think\facade\Session;
use think\facade\View;
use think\Request;
use think\response\Redirect;

class Auth
{
    /** 登录页 */
    public function login(Request $request)
    {
        if (Session::get('user_id')) {
            return redirect($this->homeFor((int)Session::get('role')));
        }
        return View::fetch('/auth/login', [
            'back' => $request->get('back', ''),
        ]);
    }

    /** 处理登录 */
    public function doLogin(Request $request)
    {
        $username = trim((string)$request->post('username', ''));
        $password = (string)$request->post('password', '');
        $back     = (string)$request->post('back', '');

        if ($username === '' || $password === '') {
            return json(['code' => 1, 'msg' => '请输入账号和密码']);
        }

        /** @var User|null $user */
        $user = User::where('username', $username)->find();
        if (!$user || !$user->verify($password)) {
            return json(['code' => 1, 'msg' => '账号或密码错误']);
        }

        Session::set('user_id', $user->id);
        Session::set('role', (int)$user->role);

        // 扫码登录回跳仅对员工开放
        if ($back !== '' && str_starts_with($back, '/scan')) {
            return json(['code' => 0, 'msg' => 'ok', 'url' => $back]);
        }
        return json(['code' => 0, 'msg' => 'ok', 'url' => $this->homeFor((int)$user->role)]);
    }

    /** 登出 */
    public function logout(): Redirect
    {
        Session::clear();
        return redirect('/login');
    }

    /** 按角色返回首页 */
    protected function homeFor(int $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN    => '/admin',
            User::ROLE_BOSS     => '/boss',
            User::ROLE_EMPLOYEE => '/employee/tasks',
            default             => '/login',
        };
    }
}
