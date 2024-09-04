<?php
return [
    'system' => [
        'timezone' => 'Europe/Budapest',
        'locale' => 'hu_HU.utf8',
        'session' => [
            'name'          => 'webapp',
            'cache_expire'  => 0,
        ],
        'logger' => [
            'name'          => 'APP',
            'format'        => '[%datetime%] %level_name% %message% %context% %extra%',
            'time_format'   => 'Y-m-d H:i:s',
            'log_dir'       => ROOT_DIR.DS.'log'
        ],
        'twig' => [
            'template_dir'  => TEMPLATES_DIR,
            'cache'         => false,
            'cache_dir'     => CACHE_DIR.DS.'twig'
        ],
        'database' => [
            'myco' => [
                'driver'    => 'mysql',
                'host'      => 'db',
                'database'  => 'teszt',
                'username'  => 'teszt',
                'password'  => 'teszt',
                'charset'   => 'utf8',
                'collation' => 'utf8_general_ci',
                'prefix'    => '',
            ]
        ]
    ],
    'middlewares' => [
        'AuthMiddleware'        => ['enabled' => 0, 'weight' => 1],
        'PermissionMiddleware'  => ['enabled' => 0, 'weight' => 2],
    ],
    'modules' => [
        'user'          => ['enabled' => 0, 'weight' => 0],
        'dashboard'     => ['enabled' => 1, 'weight' => 1],
    ]
];
?>