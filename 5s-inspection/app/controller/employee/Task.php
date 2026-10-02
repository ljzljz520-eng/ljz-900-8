<?php
declare(strict_types=1);

namespace app\controller\employee;

use app\model\Area;
use app\model\Inspection;
use app\model\Rectification;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Filesystem;
use think\facade\View;
use think\Request;

class Task
{
    /** 员工"我的任务": 指派给我 + 我可处理的待整改问题 */
    public function tasks(Request $request)
    {
        $uid    = $request->user->id;
        $status = (string)$request->get('status', '');
        $areaId = (int)$request->get('area_id', 0);

        $query = Inspection::with(['area', 'checkItem', 'rectification'])
            ->where(function ($q) use ($uid) {
                $q->where('assignee_id', $uid)->whereOr('assignee_id', null);
            })
            ->order('status', 'asc')
            ->order('inspect_date', 'desc')
            ->order('id', 'desc');

        if ($status !== '') {
            $query->where('status', (int)$status);
        }
        if ($areaId > 0) {
            $query->where('area_id', $areaId);
        }
        $list = $query->paginate(['list_rows'=>15,'query'=>request()->param()]);

        return View::fetch('/employee/tasks', [
            'list'   => $list,
            'areas'  => Area::where('status', 1)->order('sort')->select(),
            'status' => $status,
            'areaId' => $areaId,
        ]);
    }

    /** 整改表单 */
    public function repairForm(Request $request, $id)
    {
        $uid = $request->user->id;
        $row = Inspection::with(['area', 'checkItem'])->find($id);
        if (!$row) {
            return abort(404, '问题不存在');
        }
        if ((int)$row->status === Inspection::STATUS_REPAIRED) {
            return redirect('/employee/finish/' . $id);
        }
        // 仅被指派人(或开放认领)可整改
        if ($row->assignee_id && (int)$row->assignee_id !== (int)$uid) {
            return abort(403, '该问题已指派给其他同事');
        }
        return View::fetch('/employee/repair', ['row' => $row]);
    }

    /** 提交整改(现场拍照) */
    public function submit(Request $request, $id)
    {
        $uid = $request->user->id;
        $row = Inspection::find($id);
        if (!$row) {
            return json(['code' => 1, 'msg' => '问题不存在']);
        }
        if ((int)$row->status === Inspection::STATUS_REPAIRED) {
            return json(['code' => 1, 'msg' => '该问题已完成整改']);
        }
        if ($row->assignee_id && (int)$row->assignee_id !== (int)$uid) {
            return json(['code' => 1, 'msg' => '该问题未指派给你']);
        }

        $file = $request->file('repair_image');
        if (!$file) {
            return json(['code' => 1, 'msg' => '请拍摄并上传整改后的照片']);
        }

        try {
            validate([
                'file' => 'fileSize:8388608|fileExt:jpg,jpeg,png,gif,webp|fileMime:image/jpeg,image/png,image/gif,image/webp',
            ])->check(['file' => $file]);
        } catch (ValidateException $e) {
            return json(['code' => 1, 'msg' => '图片格式或大小不合法(仅支持 jpg/png/gif/webp, 最大 8MB)']);
        }

        $relPath = str_replace('\\', '/', (string)Filesystem::disk('uploads')->putFile('repair', $file));
        $remark  = mb_substr(trim((string)$request->post('remark', '')), 0, 500);

        Db::transaction(function () use ($row, $uid, $relPath, $remark) {
            Rectification::create([
                'inspection_id' => $row->id,
                'repairer_id'   => $uid,
                'repair_image'  => $relPath,
                'remark'        => $remark,
                'repair_time'   => date('Y-m-d H:i:s'),
            ]);
            $row->save(['status' => Inspection::STATUS_REPAIRED, 'assignee_id' => $uid]);
        });

        return json(['code' => 0, 'msg' => '整改提交成功', 'url' => '/employee/finish/' . $id]);
    }

    /** 整改完成页(图片对比) */
    public function finish(Request $request, $id)
    {
        $row = Inspection::with(['area', 'checkItem', 'rectification.repairer'])->find($id);
        if (!$row) {
            return abort(404, '问题不存在');
        }
        return View::fetch('/employee/finish', ['row' => $row]);
    }
}
