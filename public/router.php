<?php
// +----------------------------------------------------------------------
// | PHP 内置服务器路由： php -S localhost:8000 -t public public/router.php
// +----------------------------------------------------------------------
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// 静态文件直接返回
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';
