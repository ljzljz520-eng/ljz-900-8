<?php
declare (strict_types = 1);

namespace app\controller\staff;

use app\BaseController;
use app\model\Area;
use app\model\Inspection;
use app\model\Rectification;
use think\facade\Db;
use think\facade\Filesystem;
use think\facade\Session;
use think\facade\View;

/**
 * 员工端：扫码查看待整改问题，上传整改后图片
 */
class Rectify extends BaseController
{
    /**
     * 扫码落地页：按区域编码显示待整改问题
     * 二维码内容：/staff/scan?code=区域编码
     */
    public function scan()
    {
        $code = trim((string) input('code', ''));
        $area = Area::where('code', $code)->where('status', 1)->find();
        if (!$area) {
            return View::fetch('staff/rectify/invalid', [
                'title' => '无效二维码',
                'code'  => $code,
            ]);
        }

        $list = Inspection::with(['area', 'item'])
            ->where('area_id', $area->id)
            ->where('status', Inspection::STATUS_PENDING)
            ->order('id', 'desc')
            ->select();

        return View::fetch('staff/rectify/index', [
            'title' => '待整改',
            'area'  => $area,
            'list'  => $list,
        ]);
    }

    /**
     * 全部待整改问题
     */
    public function pending()
    {
        $list = Inspection::with(['area', 'item'])
            ->where('status', Inspection::STATUS_PENDING)
            ->order('id', 'desc')
            ->select();

        return View::fetch('staff/rectify/index', [
            'title' => '待整改',
            'area'  => null,
            'list'  => $list,
        ]);
    }

    /**
     * 整改表单
     */
    public function edit($id)
    {
        $inspection = Inspection::with(['area', 'item'])
            ->where('status', Inspection::STATUS_PENDING)
            ->find((int) $id);
        if (!$inspection) {
            Session::set('error', '该问题不存在或已整改');
            return redirect('/staff/pending');
        }

        return View::fetch('staff/rectify/edit', [
            'title'      => '问题整改',
            'inspection' => $inspection,
        ]);
    }

    /**
     * 提交整改（上传整改后图片）
     */
    public function update($id)
    {
        $inspection = Inspection::where('status', Inspection::STATUS_PENDING)->find((int) $id);
        if (!$inspection) {
            Session::set('error', '该问题不存在或已整改');
            return redirect('/staff/pending');
        }

        $file = $this->request->file('image');
        if (!$file) {
            Session::set('error', '请上传整改后图片');
            return redirect('/staff/rectify/' . (int) $id);
        }
        $ext = strtolower((string) $file->getOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || $file->getSize() > 10 * 1024 * 1024) {
            Session::set('error', '图片仅支持 jpg/png/gif/webp，且不超过 10MB');
            return redirect('/staff/rectify/' . (int) $id);
        }

        $path = str_replace('\\', '/', Filesystem::disk('public')->putFile('rectified', $file));

        // 事务：整改记录 + 更新问题状态，保证“图片对”完整
        Db::transaction(function () use ($inspection, $path) {
            Rectification::create([
                'inspection_id'   => $inspection->id,
                'user_id'         => Session::get('user.id'),
                'rectified_image' => '/storage/' . $path,
                'remark'          => trim((string) input('post.remark', '')),
            ]);
            $inspection->status = Inspection::STATUS_DONE;
            $inspection->save();
        });

        Session::set('ok', '整改完成，已提交整改照片');
        return redirect('/staff/pending');
    }
}
