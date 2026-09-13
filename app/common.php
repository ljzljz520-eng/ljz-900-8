<?php
declare(strict_types=1);

// +----------------------------------------------------------------------
// | 全局公共函数
// +----------------------------------------------------------------------

if (!function_exists('img_url')) {
    /**
     * 图片访问地址（无图返回空串，由页面占位组件兜底）
     */
    function img_url(?string $path): string
    {
        if (empty($path)) {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('status_text')) {
    function status_text(int $status): string
    {
        return match ($status) {
            0       => '待整改',
            1       => '已整改',
            2       => '已复核',
            default => '未知',
        };
    }
}

if (!function_exists('status_class')) {
    function status_class(int $status): string
    {
        return match ($status) {
            0       => 'badge-danger',
            1       => 'badge-warn',
            2       => 'badge-ok',
            default => 'badge-muted',
        };
    }
}
