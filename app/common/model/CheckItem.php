<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 5S 检查项
 */
class CheckItem extends Model
{
    protected $name = 'check_item';
    protected $autoWriteTimestamp = 'datetime';

    public const CATEGORIES = ['整理', '整顿', '清扫', '清洁', '素养'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
