<?php
declare (strict_types = 1);

namespace app\controller\boss;

use app\BaseController;
use app\model\Area;
use app\model\Inspection;
use app\model\Rectification;
use app\model\User;
use think\facade\View;

/**
 * 老板端：按员工 / 区域 / 日期查看整改前后图片对
 */
class Report extends BaseController
{
    public function index()
    {
        $userId = (int) input('user_id', 0);
        $areaId = (int) input('area_id', 0);
        $start  = trim((string) input('start', ''));
        $end    = trim((string) input('end', ''));
        $status = input('status', '');

        $query = Inspection::with(['area', 'item', 'adminUser', 'assignee', 'rectification.user'])
            ->order('id', 'desc');

        // 按区域
        if ($areaId) {
            $query->where('area_id', $areaId);
        }
        // 按整改状态
        if ($status === '0' || $status === '1') {
            $query->where('status', (int) $status);
        }
        // 按员工（整改人）
        if ($userId) {
            $ids = Rectification::where('user_id', $userId)->column('inspection_id');
            $query->where('id', 'in', $ids ?: [0]);
        }
        // 按日期范围（以检查发布时间为准）
        if ($start !== '') {
            $query->where('created_at', '>=', $start . ' 00:00:00');
        }
        if ($end !== '') {
            $query->where('created_at', '<=', $end . ' 23:59:59');
        }

        // 全局统计
        $stats = [
            'total'         => Inspection::count(),
            'pending'       => Inspection::where('status', Inspection::STATUS_PENDING)->count(),
            'done'          => Inspection::where('status', Inspection::STATUS_DONE)->count(),
            'pending_score' => Inspection::where('status', Inspection::STATUS_PENDING)->sum('deduct_score'),
        ];

        return View::fetch('boss/report/index', [
            'title'  => '整改报告',
            'list'   => $query->paginate(['list_rows' => 8, 'query' => request()->get()]),
            'areas'  => Area::where('status', 1)->order('sort')->order('id')->select(),
            'staff'  => User::where('role', 'staff')->where('status', 1)->order('id')->select(),
            'userId' => $userId,
            'areaId' => $areaId,
            'start'  => $start,
            'end'    => $end,
            'status' => $status,
            'stats'  => $stats,
        ]);
    }
}
