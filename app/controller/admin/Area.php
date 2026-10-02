<?php
declare (strict_types = 1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Area as AreaModel;
use think\facade\Session;
use think\facade\View;

/**
 * 管理员-区域与二维码管理
 */
class Area extends BaseController
{
    /**
     * 区域列表（含扫码链接与二维码）
     */
    public function index()
    {
        return View::fetch('admin/area/index', [
            'title'  => '区域二维码',
            'areas'  => AreaModel::order('sort')->order('id')->select(),
            'domain' => $this->request->domain(),
        ]);
    }

    /**
     * 新增区域
     */
    public function save()
    {
        $name = trim((string) input('post.name', ''));
        if ($name === '') {
            Session::set('error', '请填写区域名称');
            return redirect('/admin/area');
        }

        $code = strtoupper(trim((string) input('post.code', '')));
        if ($code === '') {
            $code = 'A' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 5));
        }
        if (AreaModel::where('code', $code)->find()) {
            Session::set('error', '编码 ' . $code . ' 已存在，请更换');
            return redirect('/admin/area');
        }

        AreaModel::create([
            'name'   => $name,
            'code'   => $code,
            'sort'   => (int) input('post.sort', 0),
            'status' => 1,
        ]);

        Session::set('ok', '区域已添加，可打印二维码张贴');
        return redirect('/admin/area');
    }
}
