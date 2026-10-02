<?php
declare(strict_types=1);

namespace app\middleware;

/** 仅允许角色 3 访问 */
class Boss extends Auth
{
    protected ?int $needRole = 3;
}
