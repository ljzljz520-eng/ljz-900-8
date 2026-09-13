<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\common\model\Area;
use app\common\model\Employee as EmpModel;
use app\common\model\Issue as IssueModel;
use think\facade\View;
use think\Response;
use think\response\Redirect;

class Employee extends BaseController
{
    private function done(string $msg, bool $ok = true)
    {
        if ($this->app->request->isAjax()) {
            return json(['code' => $ok ? 0 : 1, 'msg' => $msg]);
        }
        return redirect((string) url('/admin/employees'))
            ->with($ok ? 'success' : 'error', $msg);
    }

    public function index()
    {
        $list = EmpModel::with(['area'])
            ->order('id', 'asc')
            ->paginate(20);

        return View::fetch('admin/employees/index', [
            'list'  => $list,
            'areas' => Area::where('status', 1)->order('sort', 'asc')->select(),
        ]);
    }

    public function save(): Response
    {
        $post = $this->app->request->post();
        $name = trim((string) ($post['name'] ?? ''));
        $jobNo = trim((string) ($post['job_no'] ?? ''));
        $pin = (string) ($post['pin'] ?? '');

        if ($name === '' || !preg_match('/^\d{4}$/', $pin)) {
            return $this->done('请填写姓名并设置 4 位数字口令', false);
        }
        if ($jobNo !== '' && EmpModel::where('job_no', $jobNo)->count()) {
            return $this->done('工号已存在', false);
        }

        EmpModel::create([
            'name'    => $name,
            'job_no'  => $jobNo ?: null,
            'phone'   => trim((string) ($post['phone'] ?? '')),
            'pin'     => password_hash($pin, PASSWORD_DEFAULT),
            'area_id' => !empty($post['area_id']) ? (int) $post['area_id'] : null,
            'status'  => 1,
        ]);

        return $this->done('员工已添加');
    }

    public function update(int $id): Response
    {
        $emp = EmpModel::findOrFail($id);
        $post = $this->app->request->post();

        $save = [
            'name'    => trim((string) ($post['name'] ?? $emp->name)),
            'phone'   => trim((string) ($post['phone'] ?? $emp->phone)),
            'area_id' => !empty($post['area_id']) ? (int) $post['area_id'] : null,
            'status'  => isset($post['status']) ? (int) $post['status'] : $emp->status,
        ];

        // 口令留空表示不修改
        $pin = (string) ($post['pin'] ?? '');
        if ($pin !== '') {
            if (!preg_match('/^\d{4}$/', $pin)) {
                return $this->done('整改口令必须为 4 位数字', false);
            }
            $save['pin'] = password_hash($pin, PASSWORD_DEFAULT);
        }

        $jobNo = trim((string) ($post['job_no'] ?? ''));
        if ($jobNo !== '' && EmpModel::where('job_no', $jobNo)->where('id', '<>', $id)->count()) {
            return $this->done('工号已存在', false);
        }
        $save['job_no'] = $jobNo ?: null;

        $emp->save($save);
        return $this->done('员工信息已更新');
    }

    public function delete(int $id): Response
    {
        if (IssueModel::where('employee_id', $id)->count()) {
            return $this->done('该员工名下有问题单，无法删除（可停用）', false);
        }
        EmpModel::destroy($id);
        return $this->done('员工已删除');
    }
}
