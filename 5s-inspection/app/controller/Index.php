<?php
declare(strict_types=1);

namespace app\controller;

use think\facade\Session;

class Index
{
    public function index()
    {
        $role = (int)Session::get('role');
        $home = match ($role) {
            1 => '/admin',
            3 => '/boss',
            2 => '/employee/tasks',
            default => '/login',
        };
        return redirect($home);
    }
}
