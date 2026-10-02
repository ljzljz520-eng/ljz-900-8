<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use think\facade\Session;

/**
 * 入口：按角色跳转到各自工作台
 */
class Index extends BaseController
{
    public function index()
    {
        $user = Session::get('user');
        if (!$user) {
            return redirect('/login');
        }

        return match ($user['role'] ?? 'staff') {
            'admin' => redirect('/admin/inspection'),
            'boss'  => redirect('/boss/report'),
            default => redirect('/staff/pending'),
        };
    }
}
