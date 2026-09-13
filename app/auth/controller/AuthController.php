<?php
declare(strict_types=1);

namespace app\auth\controller;

use app\BaseController;
use app\common\model\AdminUser;
use think\facade\View;
use think\response\Redirect;

class AuthController extends BaseController
{
    /**
     * 登录页
     */
    public function loginForm()
    {
        if (session('user.id')) {
            return $this->redirectHome();
        }
        return View::fetch('auth/login');
    }

    /**
     * 登录处理
     */
    public function login(): Redirect
    {
        $username = trim((string) $this->app->request->post('username', ''));
        $password = (string) $this->app->request->post('password', '');

        if ($username === '' || $password === '') {
            return redirect((string) url('/login'))->with('error', '请输入账号和密码');
        }

        $user = AdminUser::where('username', $username)->find();

        if (!$user || $user->status != 1 || !password_verify($password, (string) $user->password)) {
            return redirect((string) url('/login'))->with('error', '账号或密码不正确');
        }

        $user->save([
            'login_ip' => $this->app->request->ip(),
            'login_at' => date('Y-m-d H:i:s'),
        ]);

        session('user', [
            'id'        => $user->id,
            'username'  => $user->username,
            'real_name' => $user->real_name,
            'role'      => (int) $user->role,
        ]);
        session_regenerate_id(true);

        return $this->redirectHome();
    }

    /**
     * 退出
     */
    public function logout(): Redirect
    {
        session('user', null);
        return redirect((string) url('/login'));
    }

    private function redirectHome(): Redirect
    {
        $role = (int) (session('user.role') ?? 1);
        return $role === AdminUser::ROLE_BOSS
            ? redirect((string) url('/boss'))
            : redirect((string) url('/admin'));
    }
}
