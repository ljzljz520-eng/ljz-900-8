<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 5S检查项模型（整理/整顿/清扫/清洁/素养 + 扣分标准）
 */
class InspectionItem extends Model
{
    protected $name = 'inspection_items';

    protected $autoWriteTimestamp = false;

    /** 5S分类 */
    const CATEGORIES = ['整理', '整顿', '清扫', '清洁', '素养'];
}
