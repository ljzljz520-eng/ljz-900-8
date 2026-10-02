<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 检查问题记录模型（管理员发布的问题：问题图 + 检查项 + 扣分）
 */
class Inspection extends Model
{
    protected $name = 'inspections';

    protected $autoWriteTimestamp = false;

    /** 状态：待整改 / 已整改 */
    const STATUS_PENDING = 0;
    const STATUS_DONE    = 1;

    /** 所属区域 */
    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /** 检查项 */
    public function item()
    {
        return $this->belongsTo(InspectionItem::class, 'item_id');
    }

    /** 检查人（管理员） */
    public function adminUser()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** 被指派的整改员工 */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** 整改记录（一对一） */
    public function rectification()
    {
        return $this->hasOne(Rectification::class, 'inspection_id');
    }
}
