<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 仓库区域
 */
class Area extends Model
{
    protected $name = 'area';
    protected $autoWriteTimestamp = 'datetime';

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
