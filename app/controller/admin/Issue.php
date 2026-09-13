<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\admin\validate\IssueValidate;
use app\BaseController;
use app\common\model\Area;
use app\common\model\CheckItem;
use app\common\model\Employee;
use app\common\model\Issue as IssueModel;
use app\common\service\QrcodeService;
use app\common\service\UploadService;
use think\exception\HttpException;
use think\facade\Db;
use think\facade\View;
use think\response\Json;
use think\response\Redirect;

/**
 * 问题单管理（管理员上传问题图 / 检查项 / 扣分）
 */
class Issue extends BaseController
{
    /**
     * 问题单列表 + 检索
     */
    public function index()
    {
        $get = $this->app->request->get();

        $query = IssueModel::with(['area', 'checkItem', 'employee', 'inspector']);

        if (!empty($get['area_id'])) {
            $query->where('area_id', (int) $get['area_id']);
        }
        if (isset($get['status']) && $get['status'] !== '') {
            $query->where('status', (int) $get['status']);
        }
        if (!empty($get['employee_id'])) {
            $query->where('employee_id', (int) $get['employee_id']);
        }
        if (!empty($get['start_date'])) {
            $query->where('check_date', '>=', $get['start_date']);
        }
        if (!empty($get['end_date'])) {
            $query->where('check_date', '<=', $get['end_date']);
        }
        if (!empty($get['keyword'])) {
            $kw = '%' . trim($get['keyword']) . '%';
            $query->where('code|description', 'like', $kw);
        }

        $list = $query->order('id', 'desc')
            ->paginate(15)
            ->appends($get);

        return View::fetch('admin/issues/index', [
            'list'      => $list,
            'areas'     => Area::where('status', 1)->order('sort', 'asc')->select(),
            'employees' => Employee::where('status', 1)->order('id', 'asc')->select(),
            'filters'   => [
                'area_id'     => $get['area_id'] ?? '',
                'employee_id' => $get['employee_id'] ?? '',
                'status'      => $get['status'] ?? '',
                'start_date'  => $get['start_date'] ?? '',
                'end_date'    => $get['end_date'] ?? '',
                'keyword'     => $get['keyword'] ?? '',
            ],
        ]);
    }

    /**
     * 开单页面
     */
    public function create()
    {
        // 检查项按 5S 维度分组（保持固定维度顺序）
        $items = CheckItem::where('status', 1)->order('sort', 'asc')->select();
        $groups = [];
        foreach (CheckItem::CATEGORIES as $cat) {
            $groups[$cat] = [];
        }
        foreach ($items as $it) {
            $groups[$it->category][] = $it;
        }

        return View::fetch('admin/issues/create', [
            'areas'     => Area::where('status', 1)->order('sort', 'asc')->select(),
            'employees' => Employee::with(['area'])->where('status', 1)->order('id', 'asc')->select(),
            'groups'    => $groups,
            'today'     => date('Y-m-d'),
        ]);
    }

    /**
     * 保存问题单
     */
    public function save(): Redirect
    {
        $data = $this->app->request->post();
        $this->validateData($data, IssueValidate::class);

        // 问题图必传
        $photo = $this->app->request->file('photo');
        if (!$photo) {
            return redirect((string) url('/admin/issues/create'))
                ->with('error', '请上传现场问题图')
                ->withInput();
        }

        $photoPath = UploadService::save($photo, UploadService::SCENE_BEFORE);

        Db::startTrans();
        try {
            $issue = new IssueModel();
            $issue->code = $this->genCode();
            $issue->area_id = (int) $data['area_id'];
            $issue->check_item_id = (int) $data['check_item_id'];
            $issue->inspector_id = (int) session('user.id');
            $issue->employee_id = !empty($data['employee_id']) ? (int) $data['employee_id'] : null;
            $issue->check_date = $data['check_date'];
            $issue->deduct_score = (int) $data['deduct_score'];
            $issue->description = trim((string) ($data['description'] ?? ''));
            $issue->photo_before = $photoPath;
            $issue->status = IssueModel::STATUS_PENDING;
            $issue->save();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return redirect((string) url('/admin/issues/' . $issue->id))
            ->with('success', '问题单已创建，请将二维码贴到现场，员工扫码即可整改');
    }

    /**
     * 详情（含二维码）
     */
    public function read(int $id)
    {
        $issue = IssueModel::with(['area', 'checkItem', 'employee', 'inspector'])
            ->findOrFail($id);

        return View::fetch('admin/issues/detail', [
            'issue'  => $issue,
            'qrUrl'  => (string) url('/admin/issues/' . $id . '/qrcode'),
            'domain' => $this->app->request->domain(),
        ]);
    }

    /**
     * 复核整改结果
     */
    public function review(int $id): Redirect
    {
        $issue = IssueModel::findOrFail($id);
        $action = $this->app->request->post('action', 'pass');

        if ($action === 'pass') {
            $issue->status = IssueModel::STATUS_REVIEWED;
            $issue->reviewed_at = date('Y-m-d H:i:s');
            $issue->save();
            return redirect((string) url('/admin/issues/' . $id))->with('success', '已复核通过');
        }

        // 驳回：清空整改图，回到待整改，员工需重新扫码整改
        $issue->status = IssueModel::STATUS_PENDING;
        $issue->photo_after = null;
        $issue->rectify_remark = '';
        $issue->rectified_at = null;
        $issue->reviewed_at = null;
        $issue->save();

        return redirect((string) url('/admin/issues/' . $id))->with('error', '已驳回整改，请员工重新处理');
    }

    /**
     * 输出二维码 PNG（扫码进入 /r/{code} 整改页）
     */
    public function qrcode(int $id)
    {
        $issue = IssueModel::findOrFail($id);
        $url = $this->app->request->domain() . (string) url('/r/' . $issue->code);
        $png = QrcodeService::png($url, 260);

        return response($png)->header([
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * 图片上传（AJAX，供开单页预览/未来扩展）
     */
    public function uploadImage(): Json
    {
        try {
            $file = $this->app->request->file('file');
            $path = UploadService::save($file, UploadService::SCENE_BEFORE);
            return json(['code' => 0, 'msg' => 'ok', 'data' => ['url' => img_url($path), 'path' => $path]]);
        } catch (HttpException $e) {
            return json(['code' => $e->getStatusCode(), 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 生成唯一单号 5S + 日期 + 随机串
     */
    private function genCode(): string
    {
        do {
            $code = '5S' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        } while (IssueModel::where('code', $code)->count());

        return $code;
    }
}
