<?php

use think\facade\Env;

return [
    // 默认应用
    'default_app'       => 'index',
    // 默认时区
    'default_timezone'  => Env::get('app.default_timezone', 'Asia/Shanghai'),
    // 应用映射
    'app_map'           => [],
    // 域名绑定
    'domain_bind'       => [],
    // 禁止 URL 访问的应用
    'deny_app_list'     => [],
    // 异常页面
    'exception_tmpl'    => app()->getThinkPath() . 'tpl/think_exception.tpl',
    // 错误显示信息
    'error_message'     => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'    => false,
    // 自动多应用
    'auto_multi_app'    => false,
];
