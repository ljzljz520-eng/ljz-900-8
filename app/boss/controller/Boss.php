<?php
declare(strict_types=1);

namespace app\boss\controller;

use app\BaseController;
use app\common\model\Area;
use app\common\model\Employee;
use app\common\model\Issue as IssueModel;
use think\facade\Db;
use think\facade\View;

/**
 * 老板看板：按员工 / 区域 / 日期 查看「问题图-整改图」图片对
 */
class Boss extends BaseController
{
    public function index()
    {
        $get = $this->app->request->get();

        $startDate = !empty($get['start_date']) ? $get['start_date'] : date('Y-m-01');
        $endDate   = !empty($get['end_date'])   ? $get['end_date']   : date('Y-m-d');
        $areaId    = (int) ($get['area_id'] ?? 0);
        $employeeId = (int) ($get['employee_id'] ?? 0);

        // 统一条件
        $apply = function ($query) use ($startDate, $endDate, $areaId, $employeeId) {
            $query->whereBetweenTime('check_date', $startDate, $endDate);
            if ($areaId > 0) {
                $query->where('area_id', $areaId);
            }
            if ($employeeId > 0) {
                $query->where('employee_id', $employeeId);
            }
        };

        // 汇总卡片
        $base = Db::name('issue');
        $apply($base);
        $agg = $base->field([
            'COUNT(*) AS total',
            'SUM(CASE WHEN status >= 1 THEN 1 ELSE 0 END) AS done',
            'SUM(CASE WHEN status = 0  THEN 1 ELSE 0 END) AS pending',
            'COALESCE(SUM(deduct_score),0) AS score',
        ])->find();

        $total   = (int) ($agg['total'] ?? 0);
        $done    = (int) ($agg['done'] ?? 0);
        $pending = (int) ($agg['pending'] ?? 0);

        // 图片对列表（分页）
        $listQuery = IssueModel::with(['area', 'employee', 'checkItem']);
        $apply($listQuery);
        $list = $listQuery->order('check_date', 'desc')
            ->order('id', 'desc')
            ->paginate(24)
            ->appends($get);

        // 员工排行（整改情况 / 扣分）—— 显式表名以支持 join 别名
        $prefix = (string) config('database.connections.mysql.prefix');
        $empQuery = Db::name('issue i')
            ->join($prefix . 'employee e', 'e.id = i.employee_id', 'LEFT')
            ->field([
                'i.employee_id',
                'e.name AS employee_name',
                'COUNT(*) AS cnt',
                'SUM(CASE WHEN i.status = 0 THEN 1 ELSE 0 END) AS pending_cnt',
                'COALESCE(SUM(i.deduct_score),0) AS score',
            ])
            ->whereBetweenTime('i.check_date', $startDate, $endDate);
        if ($areaId > 0) {
            $empQuery->where('i.area_id', $areaId);
        }
        $ranking = $empQuery->group('i.employee_id, e.name')
            ->order('score', 'desc')
            ->limit(10)
            ->select()->toArray();

        // 区域扣分
        $areaQuery = Db::name('issue i')
            ->join($prefix . 'area a', 'a.id = i.area_id', 'LEFT')
            ->field([
                'a.name AS area_name',
                'COUNT(*) AS cnt',
                'COALESCE(SUM(i.deduct_score),0) AS score',
                'SUM(CASE WHEN i.status = 0 THEN 1 ELSE 0 END) AS pending_cnt',
            ])
            ->whereBetweenTime('i.check_date', $startDate, $endDate);
        if ($employeeId > 0) {
            $areaQuery->where('i.employee_id', $employeeId);
        }
        $areaStats = $areaQuery->group('i.area_id, a.name')
            ->order('score', 'desc')
            ->select()->toArray();

        return View::fetch('boss/index', [
            'areas'     => Area::where('status', 1)->order('sort', 'asc')->select(),
            'employees' => Employee::where('status', 1)->order('id', 'asc')->select(),
            'list'      => $list,
            'ranking'   => $ranking,
            'areaStats' => $areaStats,
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'areaId'    => $areaId,
            'employeeId'=> $employeeId,
            'total'     => $total,
            'done'      => $done,
            'pending'   => $pending,
            'scoreSum'  => (int) ($agg['score'] ?? 0),
            'rate'      => $total > 0 ? round($done / $total * 100, 1) : 100.0,
        ]);
    }
}
