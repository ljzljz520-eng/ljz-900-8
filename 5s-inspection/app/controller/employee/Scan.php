<?php
declare(strict_types=1);

namespace app\controller\employee;

use app\model\Area;
use app\model\Inspection;
use think\facade\Session;
use think\facade\View;

class Scan
{
    /**
     * 扫码落地页 /scan/:token
     * 未登录跳登录并带回跳; 已登录展示该区域待整改问题
     */
    public function index($token)
    {
        $area = Area::where('scan_token', $token)->where('status', 1)->find();
        if (!$area) {
            return abort(404, '二维码无效或区域已停用');
        }

        // 记住本次扫码的区域, 提交整改时限定范围
        Session::set('scan_area_id', $area->id);

        $pending = Inspection::with(['checkItem', 'assignee'])
            ->where('area_id', $area->id)
            ->where('status', Inspection::STATUS_PENDING)
            ->order('id', 'desc')
            ->select();

        $repaired = Inspection::with(['checkItem', 'rectification.repairer'])
            ->where('area_id', $area->id)
            ->where('status', Inspection::STATUS_REPAIRED)
            ->order('id', 'desc')
            ->limit(10)
            ->select();

        return View::fetch('/employee/scan', [
            'area'     => $area,
            'token'    => $token,
            'pending'  => $pending,
            'repaired' => $repaired,
        ]);
    }
}
