<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use think\facade\Session;
use think\facade\View;

/**
 * 登录 / 退出
 */
class Auth extends BaseController
{
    /**
     * 登录页
     */
    public function login()
    {
        if (Session::get('user')) {
            return redirect('/');
        }
        return View::fetch('auth/login', [
            'redirect' => input('redirect', '/'),
        ]);
    }

    /**
     * 登录提交
     */
    public function doLogin()
    {
        $username = trim((string) input('post.username', ''));
        $password = (string) input('post.password', '');

        $user = User::where('username', $username)->where('status', 1)->find();
        if (!$user || !password_verify($password, $user->password)) {
            Session::set('error', '账号或密码错误');
            return redirect('/login');
        }

        Session::set('user', [
            'id'        => $user->id,
            'username'  => $user->username,
            'real_name' => $user->real_name,
            'role'      => $user->role,
        ]);

        // 仅允许站内路径，防止开放重定向
        $redirect = (string) input('post.redirect', '/');
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/';
        }
        return redirect($redirect);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        Session::delete('user');
        return redirect('/login');
    }
}
