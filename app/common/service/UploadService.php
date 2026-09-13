<?php
declare(strict_types=1);

namespace app\common\service;

use think\exception\HttpException;
use think\File;

/**
 * 现场照片上传
 * 保存到 public/uploads/{场景}/Ymd/ 下
 */
class UploadService
{
    public const SCENE_BEFORE  = 'issues';   // 问题图
    public const SCENE_AFTER   = 'rectify';  // 整改后图

    private const MAX_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOW_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * @return string 可入库的相对路径 uploads/...
     */
    public static function save(?File $file, string $scene): string
    {
        if ($file === null || !$file->isValid()) {
            throw new HttpException(422, '请选择要上传的图片');
        }

        if (!in_array(strtolower($file->extension() ?: ''), self::ALLOW_EXT, true)) {
            throw new HttpException(422, '仅支持 jpg/jpeg/png/webp/gif 格式图片');
        }

        if ($file->getSize() > self::MAX_SIZE) {
            throw new HttpException(422, '图片大小不能超过 10MB');
        }

        $dir = public_path() . 'uploads' . DIRECTORY_SEPARATOR
             . $scene . DIRECTORY_SEPARATOR . date('Ymd');
        $name = date('His') . '_' . bin2hex(random_bytes(5)) . '.' . strtolower($file->extension());

        $file->move($dir, $name);

        return 'uploads/' . $scene . '/' . date('Ymd') . '/' . $name;
    }
}
