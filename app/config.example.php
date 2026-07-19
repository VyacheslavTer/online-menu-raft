<?php

return [
    'app' => [
        'name' => 'raft',
        'subtitle' => 'Онлайн меню',
        'timezone' => 'Asia/Qyzylorda',
        'base_url' => '',
        'dev_mode' => true,
    ],

    'db' => [
        'driver' => 'sqlite',
        'sqlite_path' => __DIR__ . '/../storage/database.sqlite',
        'mysql' => [
            'host' => 'localhost',
            'port' => 3306,
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
    ],

    'admin' => [
        'email' => 'admin@example.kz',
        'password' => 'change-me-now',
        'name' => 'Администратор',
    ],

    'uploads' => [
        'dir' => __DIR__ . '/../public/uploads/menu',
        'url' => '/uploads/menu',
        'max_bytes' => 4 * 1024 * 1024,
    ],
];

