<?php

return [
    'default' => 'local',
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'storage',
        ],
        'public' => [
            'type'       => 'local',
            'root'       => public_path() . 'uploads',
            'url'        => '/uploads',
            'visibility' => 'public',
        ],
    ],
];
