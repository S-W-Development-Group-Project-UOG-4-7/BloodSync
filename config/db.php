<?php

return [
    'driver'   => 'pgsql',
    'host'     => 'localhost',
    'port'     => 5432,
    'database' => 'bloodsync',
    'username' => 'postgres',     // change if you use another user
    'password' => 'your_password', // ← replace with your actual PostgreSQL password

    // optional settings:
    'charset'  => 'utf8',
    'options'  => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];