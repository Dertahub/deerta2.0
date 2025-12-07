<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'ajcaptcha',
            'kefu',
            'treaty',
        ],
        'action_begin' => [
            'ajcaptcha',
        ],
        'config_init' => [
            'ajcaptcha',
            'betterform',
            'umeditor',
        ],
        'view_filter' => [
            'betterform',
        ],
        'upgrade' => [
            'kefu',
        ],
        'admin_login_init' => [
            'loginbg',
        ],
        'user_sidenav_after' => [
            'signin',
        ],
    ],
    'route' => [
        '/example$' => 'example/index/index',
        '/example/d/[:name]' => 'example/demo/index',
        '/example/d1/[:name]' => 'example/demo/demo1',
        '/example/d2/[:name]' => 'example/demo/demo2',
    ],
    'priority' => [],
    'domain' => '',
];
