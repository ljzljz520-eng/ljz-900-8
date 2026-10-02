<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\model\Area as AreaModel;
use think\facade\View;
use think\Request;

class Area
{
    /** 区域列表(含问题统计) */
    public function index()
    {
        $list = AreaModel::field('a.*,COUNT(i.id) AS total,'
            . 'SUM(CASE WHEN i.status=0 THEN 1 ELSE 0 END) AS pending')
            ->alias('a')
            ->leftJoin('inspection i', 'i.area_id = a.id')
            ->group('a.id')
            ->order('a.sort,a.id')
            ->select();

        return View::fetch('/admin/area_index', ['list' => $list]);
    }

    public function save(Request $request)
    {
        $id   = (int)$request->post('id', 0);
        $name = trim((string)$request->post('name', ''));
        if ($name === '') {
            return json(['code' => 1, 'msg' => '区域名称必填']);
        }
        $data = [
            'name'     => mb_substr($name, 0, 100),
            'code'     => mb_substr(trim((string)$request->post('code', '')), 0, 50),
            'location' => mb_substr(trim((string)$request->post('location', '')), 0, 200),
            'status'   => (int)$request->post('status', 1) === 0 ? 0 : 1,
            'sort'     => (int)$request->post('sort', 0),
        ];
        if ($id > 0) {
            $area = AreaModel::find($id);
            if (!$area) {
                return json(['code' => 1, 'msg' => '区域不存在']);
            }
            $area->save($data);
        } else {
            $data['scan_token'] = AreaModel::generateToken();
            AreaModel::create($data);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /** 二维码页面: 打印后张贴在区域现场 */
    public function qrcode($id)
    {
        $area = AreaModel::find($id);
        if (!$area) {
            return abort(404, '区域不存在');
        }
        $url = request()->root(true) . '/scan/' . $area->scan_token;

        return View::fetch('/admin/area_qrcode', [
            'area' => $area,
            'url'  => $url,
        ]);
    }
}
