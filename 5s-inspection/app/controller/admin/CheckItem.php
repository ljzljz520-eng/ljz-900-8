<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\model\CheckItem as CheckItemModel;
use think\facade\View;
use think\Request;

class CheckItem
{
    public function index(Request $request)
    {
        $category = trim((string)$request->get('category', ''));
        $query = CheckItemModel::order('sort,id');
        if ($category !== '') {
            $query->where('category', $category);
        }
        return View::fetch('/admin/check_item_index', [
            'list'        => $query->paginate(['list_rows'=>15,'query'=>request()->param()]),
            'category'    => $category,
            'categoryMap' => CheckItemModel::CATEGORY_MAP,
        ]);
    }

    public function save(Request $request)
    {
        $id       = (int)$request->post('id', 0);
        $category = trim((string)$request->post('category', ''));
        $title    = trim((string)$request->post('title', ''));
        if (!isset(CheckItemModel::CATEGORY_MAP[$category])) {
            return json(['code' => 1, 'msg' => '请选择正确的5S分类']);
        }
        if ($title === '') {
            return json(['code' => 1, 'msg' => '检查项标题必填']);
        }
        $score = (float)$request->post('default_score', 0);
        if ($score < 0 || $score > 100) {
            return json(['code' => 1, 'msg' => '默认扣分应在 0~100']);
        }

        $data = [
            'category'      => $category,
            'title'         => mb_substr($title, 0, 200),
            'standard'      => mb_substr(trim((string)$request->post('standard', '')), 0, 500),
            'default_score' => $score,
            'status'        => (int)$request->post('status', 1) === 0 ? 0 : 1,
            'sort'          => (int)$request->post('sort', 0),
        ];
        if ($id > 0) {
            $row = CheckItemModel::find($id);
            if (!$row) {
                return json(['code' => 1, 'msg' => '检查项不存在']);
            }
            $row->save($data);
        } else {
            CheckItemModel::create($data);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }
}
