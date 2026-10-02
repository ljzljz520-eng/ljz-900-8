<?php
// +----------------------------------------------------------------------
// | 路由定义
// +----------------------------------------------------------------------
use think\facade\Route;
use app\middleware\Auth;

// 入口与登录
Route::get('/', 'Index/index');
Route::get('login', 'Auth/login');
Route::post('login', 'Auth/doLogin');
Route::get('logout', 'Auth/logout');

// 管理员端：发布问题（问题图+检查项+扣分）、检查项、区域二维码
Route::group('admin', function () {
    Route::get('inspection', 'admin.Inspection/index');
    Route::get('inspection/create', 'admin.Inspection/create');
    Route::post('inspection', 'admin.Inspection/save');
    Route::post('inspection/delete/:id', 'admin.Inspection/delete');

    Route::get('item', 'admin.Item/index');
    Route::post('item', 'admin.Item/save');
    Route::post('item/toggle/:id', 'admin.Item/toggle');

    Route::get('area', 'admin.Area/index');
    Route::post('area', 'admin.Area/save');
})->middleware(Auth::class, 'admin');

// 员工端：扫码整改
Route::group('staff', function () {
    Route::get('scan', 'staff.Rectify/scan');          // 扫码落地（二维码内容：/staff/scan?code=区域编码）
    Route::get('pending', 'staff.Rectify/pending');    // 全部待整改
    Route::get('rectify/:id', 'staff.Rectify/edit');   // 整改表单
    Route::post('rectify/:id', 'staff.Rectify/update');// 提交整改
})->middleware(Auth::class, 'staff');

// 老板端：整改报告（图片对）
Route::group('boss', function () {
    Route::get('report', 'boss.Report/index');
})->middleware(Auth::class, 'boss');
