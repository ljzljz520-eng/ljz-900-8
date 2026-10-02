<?php
// +----------------------------------------------------------------------
// | 磁盘设置
// +----------------------------------------------------------------------
return [
    // 默认磁盘
    'default' => 'local',
    // 磁盘列表
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => runtime_path('storage'),
        ],
        'public' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径（上传的问题图/整改图存放于此，可通过 /storage 访问）
            'root'       => public_path('storage'),
            // 磁盘路径对应的外部URL路径
            'url'        => '/storage',
            // 可见性
            'visibility' => 'public',
        ],
    ],
];
