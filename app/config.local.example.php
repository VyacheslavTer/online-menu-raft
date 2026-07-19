<?php

return [
    'app' => [
        'name' => 'raft',
        'subtitle' => 'Онлайн меню',
        'timezone' => 'Asia/Qyzylorda',
        'base_url' => '',
        'dev_mode' => false,
    ],

    'db' => [
        'driver' => 'mysql',
        'sqlite_path' => __DIR__ . '/../storage/database.sqlite',
        'mysql' => [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'YOUR_DB_NAME',
            'username' => 'YOUR_DB_USER',
            'password' => 'YOUR_DB_PASSWORD',
            'charset' => 'utf8mb4',
        ],
    ],

    'admin' => [
        'email' => 'admin@example.kz',
        'password' => 'CHANGE_THIS_PASSWORD',
        'name' => 'Администратор',
    ],

    'uploads' => [
        'dir' => __DIR__ . '/../public/uploads/menu',
        'url' => '/uploads/menu',
        'max_bytes' => 4 * 1024 * 1024,
    ],
];
