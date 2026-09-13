<?php
declare(strict_types=1);

// 全局中间件（SessionInit 必须在前）
return [
    \think\middleware\SessionInit::class,
    \app\middleware\JsonBody::class,
];
