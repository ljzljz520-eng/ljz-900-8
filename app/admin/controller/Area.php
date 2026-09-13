<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\model\Area as AreaModel;
use app\common\model\Issue as IssueModel;
use think\facade\View;
use think\Response;
use think\response\Redirect;

class Area extends BaseController
{
    /** AJAX 返回 JSON，普通表单重定向回列表 */
    private function done(string $msg, bool $ok = true)
    {
        if ($this->app->request->isAjax()) {
            return json(['code' => $ok ? 0 : 1, 'msg' => $msg]);
        }
        return redirect((string) url('/admin/areas'))
            ->with($ok ? 'success' : 'error', $msg);
    }

    public function index()
    {
        $list = AreaModel::order('sort', 'asc')->order('id', 'asc')->paginate(15);
        return View::fetch('admin/areas/index', ['list' => $list]);
    }

    public function save(): Response
    {
        $post = $this->app->request->post();
        $name = trim((string) ($post['name'] ?? ''));
        if ($name === '') {
            return $this->done('区域名称不能为空', false);
        }
        if (AreaModel::where('name', $name)->count()) {
            return $this->done('区域名称已存在', false);
        }

        AreaModel::create([
            'name'    => $name,
            'code'    => trim((string) ($post['code'] ?? '')) ?: null,
            'manager' => trim((string) ($post['manager'] ?? '')),
            'sort'    => (int) ($post['sort'] ?? 0),
            'status'  => 1,
        ]);

        return $this->done('区域已添加');
    }

    public function update(int $id): Response
    {
        $area = AreaModel::findOrFail($id);
        $post = $this->app->request->post();
        $name = trim((string) ($post['name'] ?? ''));

        if ($name !== '' && AreaModel::where('name', $name)->where('id', '<>', $id)->count()) {
            return $this->done('区域名称已存在', false);
        }

        $area->save([
            'name'    => $name !== '' ? $name : $area->name,
            'code'    => trim((string) ($post['code'] ?? '')) ?: null,
            'manager' => trim((string) ($post['manager'] ?? $area->manager)),
            'sort'    => (int) ($post['sort'] ?? $area->sort),
            'status'  => isset($post['status']) ? (int) $post['status'] : $area->status,
        ]);

        return $this->done('区域已更新');
    }

    public function delete(int $id): Response
    {
        if (IssueModel::where('area_id', $id)->count()) {
            return $this->done('该区域存在问题单，无法删除（可停用）', false);
        }
        AreaModel::destroy($id);
        return $this->done('区域已删除');
    }
}
