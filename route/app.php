<?php
declare(strict_types=1);

use think\facade\Route;
use app\middleware\Auth;

// ---------------------------------------------------------------------
// 首页：按登录角色分流
// ---------------------------------------------------------------------
Route::get('/', function () {
    if (!session('user.id')) {
        return redirect((string) url('/login'));
    }
    return ((int) session('user.role')) === 2
        ? redirect((string) url('/boss'))
        : redirect((string) url('/admin'));
});

// ---------------------------------------------------------------------
// 登录 / 退出
// ---------------------------------------------------------------------
Route::get('login',   'auth/AuthController/loginForm');
Route::post('login',  'auth/AuthController/login');
Route::post('logout', 'auth/AuthController/logout');

// ---------------------------------------------------------------------
// 管理端（仅管理员）
// ---------------------------------------------------------------------
Route::group(function () {
    Route::get('admin', 'admin/Index/index');

    // 问题单（上传问题图、检查项、扣分）
    Route::get('admin/issues',            'admin/Issue/index');
    Route::get('admin/issues/create',     'admin/Issue/create');
    Route::post('admin/issues',           'admin/Issue/save');
    Route::get('admin/issues/:id',        'admin/Issue/read')->pattern(['id' => '\d+']);
    Route::post('admin/issues/:id/review','admin/Issue/review')->pattern(['id' => '\d+']);
    Route::get('admin/issues/:id/qrcode', 'admin/Issue/qrcode')->pattern(['id' => '\d+']);
    Route::post('admin/upload/image',     'admin/Issue/uploadImage');

    // 区域
    Route::get('admin/areas',             'admin/Area/index');
    Route::post('admin/areas',            'admin/Area/save');
    Route::post('admin/areas/:id',        'admin/Area/update')->pattern(['id' => '\d+']);
    Route::post('admin/areas/:id/delete', 'admin/Area/delete')->pattern(['id' => '\d+']);

    // 检查项
    Route::get('admin/items',             'admin/CheckItem/index');
    Route::post('admin/items',            'admin/CheckItem/save');
    Route::post('admin/items/:id',        'admin/CheckItem/update')->pattern(['id' => '\d+']);
    Route::post('admin/items/:id/delete', 'admin/CheckItem/delete')->pattern(['id' => '\d+']);

    // 员工
    Route::get('admin/employees',         'admin/Employee/index');
    Route::post('admin/employees',        'admin/Employee/save');
    Route::post('admin/employees/:id',    'admin/Employee/update')->pattern(['id' => '\d+']);
    Route::post('admin/employees/:id/delete', 'admin/Employee/delete')->pattern(['id' => '\d+']);
})->middleware(Auth::class, 1);

// ---------------------------------------------------------------------
// 员工扫码整改（手机端，免后台登录，凭员工口令提交）
// ---------------------------------------------------------------------
Route::get('r/:code',  'rectify/Rectify/form')->pattern(['code' => '[A-Za-z0-9]+']);
Route::post('r/:code', 'rectify/Rectify/submit')->pattern(['code' => '[A-Za-z0-9]+']);

// ---------------------------------------------------------------------
// 老板看板（管理员 + 老板均可）
// ---------------------------------------------------------------------
Route::group(function () {
    Route::get('boss', 'boss/Boss/index');
})->middleware(Auth::class, 2);
