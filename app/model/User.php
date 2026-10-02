<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 用户模型（管理员/员工/老板）
 */
class User extends Model
{
    protected $name = 'users';

    // 时间戳交给 MySQL 默认值维护
    protected $autoWriteTimestamp = false;

    // 输出时隐藏密码
    protected $hidden = ['password'];

    /** 角色常量 */
    const ROLE_ADMIN = 'admin';  // 管理员
    const ROLE_STAFF = 'staff';  // 仓库员工
    const ROLE_BOSS  = 'boss';   // 老板
}
