<?php

return [
    'default_lang'         => env('lang.default_lang', 'zh-cn'),
    'accept_language'      => [
        'zh-cn' => 'zh-cn',
        'en-us' => 'en-us',
    ],
    'use_cookie'           => true,
    'detect_var'           => 'lang',
    'cookie_var'           => 'think_lang',
    'extend_list'          => [],
    'accept_language_gzip' => true,
];
