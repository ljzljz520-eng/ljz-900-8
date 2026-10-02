<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class Inspection extends Model
{
    protected $name = 'inspection';

    public const STATUS_PENDING  = 0; // 待整改
    public const STATUS_REPAIRED = 1; // 已整改

    public const STATUS_MAP = [
        self::STATUS_PENDING  => '待整改',
        self::STATUS_REPAIRED => '已整改',
    ];

    // 生成单号 INSP + 年月日 + 3位流水
    public static function makeNo(?string $date = null): string
    {
        $date  = $date ?: date('Ymd');
        $prefix = 'INSP' . $date;
        $last  = self::whereLike('inspect_no', $prefix . '%')
            ->order('id', 'desc')->value('inspect_no');
        $seq   = $last ? ((int)substr($last, -3)) + 1 : 1;
        return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }

    public function area()      { return $this->belongsTo(Area::class, 'area_id', 'id'); }
    public function checkItem() { return $this->belongsTo(CheckItem::class, 'check_item_id', 'id'); }
    public function inspector() { return $this->belongsTo(User::class, 'inspector_id', 'id'); }
    public function assignee()  { return $this->belongsTo(User::class, 'assignee_id', 'id'); }
    public function rectification()
    {
        return $this->hasOne(Rectification::class, 'inspection_id', 'id');
    }

    // 问题图完整 URL
    public function getProblemImageUrlAttr(): string
    {
        return asset_url((string)$this->problem_image);
    }
}
