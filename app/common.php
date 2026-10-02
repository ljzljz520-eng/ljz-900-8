<?php
// 应用公共函数文件

use think\facade\Session;

if (!function_exists('current_user')) {
    /**
     * 当前登录用户
     * @return array|null
     */
    function current_user(): ?array
    {
        return Session::get('user');
    }
}
