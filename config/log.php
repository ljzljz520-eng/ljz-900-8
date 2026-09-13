<?php

use think\facade\Env;

return [
    'default'  => 'file',
    'channels' => [
        'file' => [
            'type'           => 'file',
            'path'           => '',
            'apart_level'    => [],
            'max_files'      => 0,
            'json'           => false,
            'processor'      => null,
            'close'          => false,
            'formatter'      => null,
            'format'         => '[%s][%s] %s',
            'realtime_write' => false,
        ],
    ],
];
