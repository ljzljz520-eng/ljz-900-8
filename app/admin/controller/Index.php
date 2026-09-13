<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\model\Area;
use app\common\model\Employee;
use app\common\model\Issue;
use think\facade\Db;
use think\facade\View;

/**
 * 管理员工作台
 */
class Index extends BaseController
{
    public function index()
    {
        $today = date('Y-m-d');

        // 概览统计
        $stats = [
            'total'      => Issue::count(),
            'pending'    => Issue::where('status', Issue::STATUS_PENDING)->count(),
            'done'       => Issue::where('status', '>=', Issue::STATUS_DONE)->count(),
            'today'      => Issue::where('check_date', $today)->count(),
            'deductSum'  => (int) Issue::sum('deduct_score'),
            'areaCount'  => Area::where('status', 1)->count(),
            'staffCount' => Employee::where('status', 1)->count(),
        ];
        $stats['rate'] = $stats['total'] > 0
            ? round($stats['done'] / $stats['total'] * 100, 1)
            : 100.0;

        // 待整改清单（未整改项占位提醒）
        $pendingList = Issue::with(['area', 'checkItem', 'employee'])
            ->where('status', Issue::STATUS_PENDING)
            ->order('check_date', 'desc')
            ->order('id', 'desc')
            ->limit(8)
            ->select();

        // 近 7 天扣分趋势
        $trend = Db::name('issue')
            ->field("check_date, COUNT(*) AS cnt, SUM(deduct_score) AS score")
            ->where('check_date', '>=', date('Y-m-d', strtotime('-6 days')))
            ->group('check_date')
            ->order('check_date', 'asc')
            ->select()
            ->toArray();

        return View::fetch('admin/index', [
            'stats'       => $stats,
            'pendingList' => $pendingList,
            'trend'       => $trend,
        ]);
    }
}
