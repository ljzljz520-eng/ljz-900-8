<?php
// 应用公共函数文件

/**
 * 把存储相对路径转为可访问 URL
 *  - demo/xxx.svg        => /storage/demo/xxx.svg
 *  - problem/xxx.jpg     => /uploads/problem/xxx.jpg
 */
function asset_url(?string $path): string
{
    if (empty($path)) {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }
    if (str_starts_with($path, 'demo/')) {
        return '/storage/' . $path;
    }
    return '/uploads/' . $path;
}

/**
 * 中文友好的金额/分数格式
 */
function score_fmt($score): string
{
    return number_format((float)$score, 2);
}
