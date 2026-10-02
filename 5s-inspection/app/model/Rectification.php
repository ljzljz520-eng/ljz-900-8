<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class Rectification extends Model
{
    protected $name = 'rectification';

    public function inspection() { return $this->belongsTo(Inspection::class, 'inspection_id', 'id'); }
    public function repairer()   { return $this->belongsTo(User::class, 'repairer_id', 'id'); }

    public function getRepairImageUrlAttr(): string
    {
        return asset_url((string)$this->repair_image);
    }
}
