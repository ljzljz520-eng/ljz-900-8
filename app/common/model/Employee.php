<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 仓库员工
 */
class Employee extends Model
{
    protected $name = 'employee';
    protected $autoWriteTimestamp = 'datetime';
    protected $hidden = ['pin'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
