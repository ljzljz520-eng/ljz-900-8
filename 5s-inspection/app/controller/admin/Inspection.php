<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\model\Area;
use app\model\CheckItem;
use app\model\Inspection as InspectionModel;
use app\model\Rectification;
use app\model\User;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Filesystem;
use think\facade\View;
use think\Request;

class Inspection
{
    /** 问题列表 */
    public function index(Request $request)
    {
        $status   = $request->get('status', '');
        $areaId   = (int)$request->get('area_id', 0);
        $keyword  = trim((string)$request->get('keyword', ''));
        $date     = $request->get('date', '');

        $query = InspectionModel::with(['area', 'checkItem', 'inspector', 'assignee', 'rectification.repairer'])
            ->order('id', 'desc');

        if ($status !== '') {
            $query->where('status', (int)$status);
        }
        if ($areaId > 0) {
            $query->where('area_id', $areaId);
        }
        if ($date !== '') {
            $query->where('inspect_date', $date);
        }
        if ($keyword !== '') {
            $query->whereLike('inspect_no|description', "%{$keyword}%");
        }

        $list = $query->paginate(['list_rows'=>15,'query'=>request()->param()]);

        return View::fetch('/admin/inspection_index', [
            'list'      => $list,
            'areas'     => Area::where('status', 1)->order('sort')->select(),
            'status'    => $status,
            'areaId'    => $areaId,
            'keyword'   => $keyword,
            'date'      => $date,
        ]);
    }

    /** 新建问题表单 */
    public function create()
    {
        return View::fetch('/admin/inspection_form', [
            'areas' => Area::where('status', 1)->order('sort')->select(),
            'items' => CheckItem::where('status', 1)->order('sort')->select(),
            'staff' => User::where('role', User::ROLE_EMPLOYEE)
                ->where('status', 1)->order('id')->select(),
            'data'  => null,
        ]);
    }

    /** 保存新问题 */
    public function save(Request $request)
    {
        $data = $this->validateData($request);

        // 问题图片上传 problem/
        $file = $request->file('problem_image');
        if (!$file) {
            return json(['code' => 1, 'msg' => '请上传问题图片']);
        }
        $relPath = $this->uploadImage($file, 'problem');
        if ($relPath === '') {
            return json(['code' => 1, 'msg' => '图片格式或大小不合法(仅支持 jpg/png/gif/webp, 最大 8MB)']);
        }

        Db::transaction(function () use ($data, $relPath, $request) {
            InspectionModel::create([
                'inspect_no'    => InspectionModel::makeNo(),
                'area_id'       => $data['area_id'],
                'check_item_id' => $data['check_item_id'],
                'inspector_id'  => $request->user->id,
                'assignee_id'   => $data['assignee_id'] ?: null,
                'problem_image' => $relPath,
                'description'   => $data['description'],
                'deduct_score'  => $data['deduct_score'],
                'status'        => InspectionModel::STATUS_PENDING,
                'inspect_date'  => $data['inspect_date'],
                'deadline'      => $data['deadline'] ?: null,
            ]);
        });

        return json(['code' => 0, 'msg' => '问题已发布,等待员工扫码整改', 'url' => '/admin/inspection']);
    }

    /** 问题详情 + 整改图 */
    public function detail(Request $request, $id)
    {
        $row = InspectionModel::with(['area', 'checkItem', 'inspector', 'assignee', 'rectification.repairer'])
            ->find($id);
        if (!$row) {
            return abort(404, '问题不存在');
        }
        return View::fetch('/admin/inspection_detail', ['row' => $row]);
    }

    /** 删除(连带整改记录) */
    public function delete($id)
    {
        $row = InspectionModel::find($id);
        if (!$row) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }
        Db::transaction(function () use ($row) {
            Rectification::where('inspection_id', $row->id)->delete();
            $row->delete();
        });
        return json(['code' => 0, 'msg' => '已删除']);
    }

    /**
     * 校验表单
     * @return array<string,mixed>
     */
    protected function validateData(Request $request): array
    {
        $areaId   = (int)$request->post('area_id', 0);
        $itemId   = (int)$request->post('check_item_id', 0);
        $score    = (float)$request->post('deduct_score', 0);
        $date     = trim((string)$request->post('inspect_date', ''));

        if ($areaId <= 0 || !Area::find($areaId)) {
            throw new ValidateException('请选择正确的检查区域');
        }
        if ($itemId <= 0 || !CheckItem::find($itemId)) {
            throw new ValidateException('请选择检查项');
        }
        if ($score < 0 || $score > 100) {
            throw new ValidateException('扣分值应在 0~100 之间');
        }
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new ValidateException('请选择检查日期');
        }

        return [
            'area_id'       => $areaId,
            'check_item_id' => $itemId,
            'assignee_id'   => (int)$request->post('assignee_id', 0),
            'description'   => mb_substr(trim((string)$request->post('description', '')), 0, 500),
            'deduct_score'  => $score,
            'inspect_date'  => $date,
            'deadline'      => trim((string)$request->post('deadline', '')),
        ];
    }

    /** 上传图片到 public/uploads/{dir}, 返回相对路径 */
    protected function uploadImage($file, string $dir): string
    {
        try {
            validate([
                'file' => 'fileSize:8388608|fileExt:jpg,jpeg,png,gif,webp|fileMime:image/jpeg,image/png,image/gif,image/webp',
            ])->check(['file' => $file]);
        } catch (ValidateException $e) {
            return '';
        }
        $savename = Filesystem::disk('uploads')->putFile($dir, $file);
        // putFile 返回形如 problem/20261002/xxx.jpg
        return str_replace('\\', '/', $savename);
    }
}
