<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class CheckItem extends Model
{
    protected $name = 'check_item';

    public const CATEGORY_MAP = [
        'SEIRI'    => '整理 (Seiri)',
        'SEITON'   => '整顿 (Seiton)',
        'SEISO'    => '清扫 (Seiso)',
        'SEIKETSU' => '清洁 (Seiketsu)',
        'SHITSUKE' => '素养 (Shitsuke)',
    ];
}
