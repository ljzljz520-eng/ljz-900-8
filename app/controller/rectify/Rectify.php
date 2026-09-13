<?php
declare(strict_types=1);

namespace app\controller\rectify;

use app\BaseController;
use app\common\model\Employee;
use app\common\model\Issue as IssueModel;
use app\common\service\UploadService;
use think\exception\HttpException;
use think\facade\View;
use think\response\Redirect;

/**
 * 员工扫码整改（移动端，无需后台登录）
 */
class Rectify extends BaseController
{
    /**
     * 整改页面
     */
    public function form(string $code)
    {
        $issue = $this->findIssue($code);

        return View::fetch('rectify/form', [
            'issue'     => $issue,
            'employees' => Employee::with(['area'])
                ->where('status', 1)
                ->order('id', 'asc')
                ->select(),
        ]);
    }

    /**
     * 提交整改（整改后图 + 员工口令确认）
     */
    public function submit(string $code): Redirect
    {
        $issue = $this->findIssue($code);
        $req = $this->app->request;
        $url = (string) url('/r/' . $code);

        if ($issue->status >= IssueModel::STATUS_REVIEWED) {
            return redirect($url)->with('error', '该问题单已复核，无需再次整改');
        }

        $employeeId = (int) $req->post('employee_id', 0);
        $pin = (string) $req->post('pin', '');
        $remark = trim((string) $req->post('remark', ''));

        $employee = Employee::where('id', $employeeId)->where('status', 1)->find();
        if (!$employee) {
            return redirect($url)->with('error', '请选择整改责任人');
        }
        if (!preg_match('/^\d{4}$/', $pin) || !password_verify($pin, (string) $employee->pin)) {
            return redirect($url)->with('error', '整改口令不正确，请联系班组长');
        }

        $photo = $req->file('photo_after');
        if (!$photo) {
            return redirect($url)->with('error', '请拍摄并上传整改后的现场照片');
        }

        $path = UploadService::save($photo, UploadService::SCENE_AFTER);

        $issue->save([
            'employee_id'    => $employee->id,
            'photo_after'    => $path,
            'rectify_remark' => $remark,
            'status'         => IssueModel::STATUS_DONE,
            'rectified_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect($url)->with('success', '整改已提交，等待管理员复核！');
    }

    private function findIssue(string $code): IssueModel
    {
        $issue = IssueModel::with(['area', 'checkItem', 'employee'])
            ->where('code', $code)
            ->find();

        if (!$issue) {
            throw new HttpException(404, '整改单不存在或已失效');
        }
        return $issue;
    }
}
