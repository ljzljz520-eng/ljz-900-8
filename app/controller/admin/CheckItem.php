<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\common\model\CheckItem as ItemModel;
use app\common\model\Issue as IssueModel;
use think\facade\View;
use think\Response;
use think\response\Redirect;

class CheckItem extends BaseController
{
    private function done(string $msg, bool $ok = true)
    {
        if ($this->app->request->isAjax()) {
            return json(['code' => $ok ? 0 : 1, 'msg' => $msg]);
        }
        return redirect((string) url('/admin/items'))
            ->with($ok ? 'success' : 'error', $msg);
    }

    public function index()
    {
        $list = ItemModel::order('category', 'asc')
            ->order('sort', 'asc')
            ->paginate(20);

        return View::fetch('admin/items/index', [
            'list'       => $list,
            'categories' => ItemModel::CATEGORIES,
        ]);
    }

    public function save(): Response
    {
        $post = $this->app->request->post();
        $category = trim((string) ($post['category'] ?? ''));
        $content = trim((string) ($post['content'] ?? ''));

        if (!in_array($category, ItemModel::CATEGORIES, true) || $content === '') {
            return $this->done('请填写 5S 分类和检查内容', false);
        }

        ItemModel::create([
            'category'  => $category,
            'content'   => $content,
            'max_score' => max(0, min(20, (int) ($post['max_score'] ?? 5))),
            'sort'      => (int) ($post['sort'] ?? 0),
            'status'    => 1,
        ]);

        return $this->done('检查项已添加');
    }

    public function update(int $id): Response
    {
        $item = ItemModel::findOrFail($id);
        $post = $this->app->request->post();

        $item->save([
            'category'  => in_array($post['category'] ?? '', ItemModel::CATEGORIES, true)
                            ? $post['category'] : $item->category,
            'content'   => trim((string) ($post['content'] ?? $item->content)),
            'max_score' => max(0, min(20, (int) ($post['max_score'] ?? $item->max_score))),
            'sort'      => (int) ($post['sort'] ?? $item->sort),
            'status'    => isset($post['status']) ? (int) $post['status'] : $item->status,
        ]);

        return $this->done('检查项已更新');
    }

    public function delete(int $id): Response
    {
        if (IssueModel::where('check_item_id', $id)->count()) {
            return $this->done('该检查项已被问题单引用，无法删除（可停用）', false);
        }
        ItemModel::destroy($id);
        return $this->done('检查项已删除');
    }
}
