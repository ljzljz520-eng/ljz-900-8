<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户模型
 * @property int    $id
 * @property string $username
 * @property string $real_name
 * @property int    $role   1管理员 2员工 3老板
 * @property int    $status
 */
class User extends Model
{
    protected $name = 'user';
    protected $pk   = 'id';

    public const ROLE_ADMIN    = 1;
    public const ROLE_EMPLOYEE = 2;
    public const ROLE_BOSS     = 3;

    public const ROLE_MAP = [
        self::ROLE_ADMIN    => '管理员',
        self::ROLE_EMPLOYEE => '员工',
        self::ROLE_BOSS     => '老板',
    ];

    public function isAdmin(): bool    { return (int)$this->role === self::ROLE_ADMIN; }
    public function isEmployee(): bool { return (int)$this->role === self::ROLE_EMPLOYEE; }
    public function isBoss(): bool     { return (int)$this->role === self::ROLE_BOSS; }

    public function verify(string $password): bool
    {
        return $this->status == 1 && password_verify($password, (string)$this->password);
    }
}
