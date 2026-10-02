<?php
declare(strict_types=1);

namespace app\middleware;

/** 仅允许角色 1 访问 */
class Admin extends Auth
{
    protected ?int $needRole = 1;
}
