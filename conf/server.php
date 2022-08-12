<?php

$CMDEConf = [
    'DB' => [
        'user' => 'cmde',
        'pass' => 'cmde',
        'pdo-string' => 'mysql:dbname=cmde;unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4'
    ],
    'STORAGE' => [
        'path' => dirname(__FILE__) . '/../upload/'
    ]
];