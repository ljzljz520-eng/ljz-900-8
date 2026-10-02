<?php
use think\facade\Route;

// 首页: 按登录角色跳转
Route::get('/', 'Index/index');

// ---------- 登录 / 登出 ----------
Route::get('login', 'Auth/login');
Route::post('login', 'Auth/doLogin');
Route::get('logout', 'Auth/logout');

// ---------- 员工扫码入口(未登录中间件会带 back 跳登录) ----------
Route::get('scan/:token', 'employee/Scan/index')
    ->middleware(\app\middleware\Employee::class);

// ---------- 员工端 ----------
Route::group('employee', function () {
    Route::get('tasks',           'Task/tasks');
    Route::get('repair/:id',      'Task/repairForm');
    Route::post('submit/:id',     'Task/submit');
    Route::get('finish/:id',      'Task/finish');
})->prefix('employee/')->middleware(\app\middleware\Employee::class);

// ---------- 管理员端 ----------
Route::group('admin', function () {
    Route::get('/', 'Dashboard/index');

    Route::group('inspection', function () {
        Route::get('/',           'Inspection/index');
        Route::get('create',      'Inspection/create');
        Route::post('save',       'Inspection/save');
        Route::get('detail/:id',  'Inspection/detail');
        Route::post('delete/:id', 'Inspection/delete');
    });

    Route::group('area', function () {
        Route::get('/',          'Area/index');
        Route::post('save',      'Area/save');
        Route::get('qrcode/:id', 'Area/qrcode');
    });

    Route::group('check-item', function () {
        Route::get('/',     'CheckItem/index');
        Route::post('save', 'CheckItem/save');
    });
})->prefix('admin/')->middleware(\app\middleware\Admin::class);

// ---------- 老板端 ----------
Route::group('boss', function () {
    Route::get('/', 'Report/index');
})->prefix('boss/')->middleware(\app\middleware\Boss::class);
