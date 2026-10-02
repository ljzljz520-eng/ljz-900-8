<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\model\Area;
use app\model\Inspection;
use think\facade\View;

class Dashboard
{
    /** 管理员首页: 统计卡片 + 最近问题 */
    public function index()
    {
        $total    = Inspection::count();
        $pending  = Inspection::where('status', Inspection::STATUS_PENDING)->count();
        $repaired = Inspection::where('status', Inspection::STATUS_REPAIRED)->count();
        $rate     = $total > 0 ? round($repaired / $total * 100, 1) : 0.0;
        $deduct   = (float)Inspection::sum('deduct_score');

        // 各区域待整改数
        $areaStats = Area::field('a.id,a.name,COUNT(i.id) AS total,'
            . 'SUM(CASE WHEN i.status=0 THEN 1 ELSE 0 END) AS pending')
            ->alias('a')
            ->leftJoin('inspection i', 'i.area_id = a.id')
            ->where('a.status', 1)
            ->group('a.id,a.name,a.sort')
            ->order('a.sort')
            ->select();

        // 近7天趋势(按日期聚合格式化放在 PHP 层, 兼容 MySQL/SQLite)
        $rows = Inspection::field("inspect_date AS d, COUNT(*) AS total,"
            . "SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) AS pending")
            ->where('inspect_date', '>=', date('Y-m-d', strtotime('-6 days')))
            ->group('inspect_date')->order('d')->select();
        $trend = array_map(static function ($r) {
            $r['d'] = date('m-d', strtotime((string)$r['d']));
            return $r;
        }, $rows->toArray());

        $latest = Inspection::with(['area', 'checkItem', 'assignee'])
            ->order('id', 'desc')->limit(8)->select();

        return View::fetch('/admin/dashboard', [
            'total'     => $total,
            'pending'   => $pending,
            'repaired'  => $repaired,
            'rate'      => $rate,
            'deduct'    => $deduct,
            'areaStats' => $areaStats,
            'trend'     => $trend,
            'latest'    => $latest,
        ]);
    }
}
