<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 仓库区域模型（每个区域对应一个二维码 code）
 */
class Area extends Model
{
    protected $name = 'areas';

    protected $autoWriteTimestamp = false;

    /**
     * 该区域下的问题记录
     */
    public function inspections()
    {
        return $this->hasMany(Inspection::class, 'area_id');
    }
}
