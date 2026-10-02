<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

class Area extends Model
{
    protected $name = 'area';

    public static function generateToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (self::where('scan_token', $token)->count() > 0);

        return $token;
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class, 'area_id', 'id');
    }
}
