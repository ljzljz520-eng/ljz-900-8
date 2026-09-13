<?php

use think\facade\Env;

return [
    'default'     => 'mysql',
    'connections' => [
        'mysql' => [
            'type'            => Env::get('database.type', 'mysql'),
            'hostname'        => Env::get('database.hostname', '127.0.0.1'),
            'database'        => Env::get('database.database', 'warehouse_5s'),
            'username'        => Env::get('database.username', 'root'),
            'password'        => Env::get('database.password', ''),
            'hostport'        => Env::get('database.hostport', '3306'),
            'charset'         => Env::get('database.charset', 'utf8mb4'),
            'prefix'          => Env::get('database.prefix', 'wa_'),
            'prefix_score'    => 'str_',
            'schema'          => '',
            'deploy'          => 0,
            'rw_separate'     => false,
            'master_num'      => 1,
            'slave_no'        => '',
            'fields_strict'   => true,
            'trigger_sql'     => true,
            'debug'           => Env::get('app.debug', false),
            'fields_cache'    => false,
        ],
    ],
];
