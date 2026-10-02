<?php
declare (strict_types = 1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Area;
use app\model\Inspection as InspectionModel;
use app\model\InspectionItem;
use app\model\User;
use think\facade\Filesystem;
use think\facade\Session;
use think\facade\View;

/**
 * 管理员-问题管理：上传问题图、选择检查项、扣分
 */
class Inspection extends BaseController
{
    /**
     * 问题列表
     */
    public function index()
    {
        $areaId = (int) input('area_id', 0);
        $status = input('status', '');

        $query = InspectionModel::with(['area', 'item', 'assignee', 'rectification.user'])
            ->order('id', 'desc');
        if ($areaId) {
            $query->where('area_id', $areaId);
        }
        if ($status === '0' || $status === '1') {
            $query->where('status', (int) $status);
        }

        return View::fetch('admin/inspection/index', [
            'title'  => '问题记录',
            'list'   => $query->paginate(['list_rows' => 15, 'query' => request()->get()]),
            'areas'  => Area::where('status', 1)->order('sort')->order('id')->select(),
            'areaId' => $areaId,
            'status' => $status,
        ]);
    }

    /**
     * 发布问题页
     */
    public function create()
    {
        return View::fetch('admin/inspection/create', [
            'title' => '发布问题',
            'areas' => Area::where('status', 1)->order('sort')->order('id')->select(),
            'items' => InspectionItem::where('status', 1)->order('id')->select(),
            'staff' => User::where('role', 'staff')->where('status', 1)->order('id')->select(),
        ]);
    }

    /**
     * 保存问题
     */
    public function save()
    {
        $areaId = (int) input('post.area_id', 0);
        $itemId = (int) input('post.item_id', 0);
        if (!$areaId || !$itemId) {
            Session::set('error', '请选择区域和检查项');
            return redirect('/admin/inspection/create');
        }

        $file = $this->request->file('image');
        if (!$file) {
            Session::set('error', '请上传问题图片');
            return redirect('/admin/inspection/create');
        }
        $error = $this->checkImage($file);
        if ($error) {
            Session::set('error', $error);
            return redirect('/admin/inspection/create');
        }

        $path = str_replace('\\', '/', Filesystem::disk('public')->putFile('problem', $file));

        // 扣分：留空则按检查项标准扣分
        $item  = InspectionItem::find($itemId);
        $score = input('post.deduct_score', '');
        $score = ($score === '' || $score === null) ? (float) ($item->deduct_score ?? 0) : (float) $score;

        InspectionModel::create([
            'area_id'       => $areaId,
            'item_id'       => $itemId,
            'admin_id'      => Session::get('user.id'),
            'assignee_id'   => (int) input('post.assignee_id', 0) ?: null,
            'problem_image' => '/storage/' . $path,
            'deduct_score'  => $score,
            'remark'        => trim((string) input('post.remark', '')),
            'status'        => InspectionModel::STATUS_PENDING,
        ]);

        Session::set('ok', '问题已发布，等待员工整改');
        return redirect('/admin/inspection');
    }

    /**
     * 删除问题（整改记录随外键级联删除）
     */
    public function delete($id)
    {
        InspectionModel::destroy((int) $id);
        Session::set('ok', '已删除');
        return redirect('/admin/inspection');
    }

    /**
     * 校验上传图片
     */
    private function checkImage($file): ?string
    {
        $ext = strtolower((string) $file->getOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return '图片仅支持 jpg / png / gif / webp 格式';
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            return '图片大小不能超过 10MB';
        }
        return null;
    }
}
