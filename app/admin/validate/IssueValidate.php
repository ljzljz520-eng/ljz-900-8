<?php
declare(strict_types=1);

namespace app\admin\validate;

use think\Validate;

class IssueValidate extends Validate
{
    protected $rule = [
        'area_id'       => 'require|integer|gt:0',
        'check_item_id' => 'require|integer|gt:0',
        'employee_id'   => 'integer|egt:0',
        'check_date'    => 'require|date',
        'deduct_score'  => 'require|integer|between:0,20',
        'description'   => 'max:500',
    ];

    protected $message = [
        'area_id.require'       => '请选择所在区域',
        'check_item_id.require' => '请选择检查项',
        'check_date.require'    => '请选择检查日期',
        'deduct_score.require'  => '请填写扣分',
        'deduct_score.between'  => '扣分应在 0-20 分之间',
        'description.max'       => '问题描述不超过 500 字',
    ];
}
