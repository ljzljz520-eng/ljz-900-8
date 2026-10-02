<?php
declare(strict_types=1);

namespace app\controller\boss;

use app\model\Area;
use app\model\Inspection;
use app\model\User;
use think\facade\View;
use think\Request;

class Report
{
    /**
     * 老板看板
     * 筛选: 员工(整改人) / 区域 / 检查日期(单日或区间)
     */
    public function index(Request $request)
    {
        $repairerId = (int)$request->get('repairer_id', 0);
        $areaId     = (int)$request->get('area_id', 0);
        $startDate  = trim((string)$request->get('start_date', ''));
        $endDate    = trim((string)$request->get('end_date', ''));
        $status     = (string)$request->get('status', '');

        $query = Inspection::alias('i')
            ->leftJoin('area a', 'a.id = i.area_id')
            ->leftJoin('check_item c', 'c.id = i.check_item_id')
            ->leftJoin('user u1', 'u1.id = i.inspector_id')
            ->leftJoin('rectification r', 'r.inspection_id = i.id')
            ->leftJoin('user u2', 'u2.id = r.repairer_id')
            ->field('i.*, a.name AS area_name, c.title AS item_title, c.category,'
                . 'u1.real_name AS inspector_name,'
                . 'u2.real_name AS repairer_name, r.repair_image, r.remark AS repair_remark, r.repair_time');

        if ($repairerId > 0) {
            // 员工维度: 看该员工整改的; 未整改项可通过"含待整改"开关查看
            $query->where(function ($q) use ($repairerId) {
                $q->where('r.repairer_id', $repairerId)
                  ->whereOr('i.assignee_id', $repairerId);
            });
        }
        if ($areaId > 0) {
            $query->where('i.area_id', $areaId);
        }
        if ($startDate !== '') {
            $query->where('i.inspect_date', '>=', $startDate);
        }
        if ($endDate !== '') {
            $query->where('i.inspect_date', '<=', $endDate);
        }
        if ($status !== '') {
            $query->where('i.status', (int)$status);
        }

        $list = $query->order('i.inspect_date', 'desc')->order('i.id', 'desc')
            ->paginate(['list_rows'=>20,'query'=>request()->param()]);

        // 汇总: 总数/已整改/待整改/扣分合计
        $stat = [
            'total'    => Inspection::count(),
            'pending'  => Inspection::where('status', 0)->count(),
            'repaired' => Inspection::where('status', 1)->count(),
            'deduct'   => (float)Inspection::sum('deduct_score'),
        ];
        // 员工整改排行(使用带前缀的模型查询, 避免硬编码表名)
        $ranking = \app\model\Rectification::alias('r')
            ->leftJoin('user u', 'u.id = r.repairer_id')
            ->field('u.id,u.real_name,COUNT(*) AS cnt,MAX(r.repair_time) AS last_time')
            ->group('u.id,u.real_name')->order('cnt', 'desc')->limit(10)->select();

        return View::fetch('/boss/report', [
            'list'       => $list,
            'staff'      => User::where('role', User::ROLE_EMPLOYEE)->where('status', 1)->order('id')->select(),
            'areas'      => Area::where('status', 1)->order('sort')->select(),
            'repairerId' => $repairerId,
            'areaId'     => $areaId,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'status'     => $status,
            'stat'       => $stat,
            'ranking'    => $ranking,
            'categoryMap' => \app\model\CheckItem::CATEGORY_MAP,
        ]);
    }
}
