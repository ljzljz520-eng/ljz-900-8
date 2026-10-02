<?php
declare (strict_types = 1);

namespace app\controller\admin;

use app\BaseController;
use app\model\InspectionItem;
use think\facade\Session;
use think\facade\View;

/**
 * 管理员-5S检查项管理
 */
class Item extends BaseController
{
    /**
     * 检查项列表
     */
    public function index()
    {
        return View::fetch('admin/item/index', [
            'title'      => '检查项管理',
            'items'      => InspectionItem::order('id', 'desc')->select(),
            'categories' => InspectionItem::CATEGORIES,
        ]);
    }

    /**
     * 新增检查项
     */
    public function save()
    {
        $name     = trim((string) input('post.name', ''));
        $category = (string) input('post.category', '整理');
        $score    = (float) input('post.deduct_score', 1);

        if ($name === '') {
            Session::set('error', '请填写检查项名称');
            return redirect('/admin/item');
        }
        if (!in_array($category, InspectionItem::CATEGORIES, true)) {
            $category = '整理';
        }

        InspectionItem::create([
            'name'         => $name,
            'category'     => $category,
            'deduct_score' => $score,
            'status'       => 1,
        ]);

        Session::set('ok', '检查项已添加');
        return redirect('/admin/item');
    }

    /**
     * 启用 / 停用
     */
    public function toggle($id)
    {
        $item = InspectionItem::find((int) $id);
        if ($item) {
            $item->status = $item->status ? 0 : 1;
            $item->save();
            Session::set('ok', '状态已更新');
        }
        return redirect('/admin/item');
    }
}
