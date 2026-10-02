<?php
// 内置服务器路由文件：php -S 0.0.0.0:8000 -t public public/router.php
if (is_file($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'])) {
    return false;
}
require __DIR__ . '/index.php';
