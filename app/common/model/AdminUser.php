<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 后台用户
 * @property int    $id
 * @property string $username
 * @property string $real_name
 * @property int    $role   1管理员 2老板
 * @property int    $status
 */
class AdminUser extends Model
{
    protected $name = 'admin_user';
    protected $autoWriteTimestamp = 'datetime';
    protected $hidden = ['password'];

    public const ROLE_ADMIN = 1;
    public const ROLE_BOSS  = 2;
}
