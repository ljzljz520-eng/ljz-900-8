<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 检查问题单（核心：图片对）
 *
 * @property int    $id
 * @property string $code
 * @property int    $area_id
 * @property int    $check_item_id
 * @property int    $inspector_id
 * @property int    $employee_id
 * @property string $check_date
 * @property int    $deduct_score
 * @property string $photo_before
 * @property string $photo_after
 * @property int    $status  0待整改 1已整改 2已复核
 */
class Issue extends Model
{
    protected $name = 'issue';
    protected $autoWriteTimestamp = 'datetime';

    public const STATUS_PENDING  = 0;
    public const STATUS_DONE     = 1;
    public const STATUS_REVIEWED = 2;

    // 关联区域
    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    // 关联检查项
    public function checkItem()
    {
        return $this->belongsTo(CheckItem::class, 'check_item_id');
    }

    // 开单管理员
    public function inspector()
    {
        return $this->belongsTo(AdminUser::class, 'inspector_id');
    }

    // 整改员工
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // 是否未整改（用于占位提醒）
    public function getIsRectifiedAttr(): bool
    {
        return !empty($this->photo_after) && $this->status >= self::STATUS_DONE;
    }
}
