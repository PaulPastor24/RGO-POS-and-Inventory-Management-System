<?php

declare(strict_types=1);

const APP_NAME = 'BatState-U RGO Ordering System';
const APP_TIMEZONE = 'Asia/Manila';
const APP_DATA_DIR = __DIR__ . '/../data';

// Database settings.
// Supported drivers: sqlite, mysql
const DB_DRIVER = 'mysql';

// SQLite file (used when DB_DRIVER=sqlite).
const SQLITE_DB_FILE = APP_DATA_DIR . '/batstate_rgo.db';

// MySQL settings (used when DB_DRIVER=mysql).
const MYSQL_HOST = '127.0.0.1';
const MYSQL_PORT = 3306;
const MYSQL_DATABASE = 'capstone_rgo';
const MYSQL_USERNAME = 'root';
const MYSQL_PASSWORD = '';
const MYSQL_CHARSET = 'utf8mb4';

// Role-based allowed accounts.
const ADMIN_EMAILS = [
    'admin@g.batstate-u.edu.ph',
];

const STAFF_EMAILS = [
    'staff@g.batstate-u.edu.ph',
];

const LOCAL_LOGIN_USERS = [
    [
        'email' => 'admin@g.batstate-u.edu.ph',
        'name' => 'RGO Administrator',
        'password_hash' => '$2y$10$juVUYCgHxbcUdqeBNuHfWu5fYs.4QsUZD1Yb8Ry72XPBF6Gk2Ue3O',
    ],
    [
        'email' => 'staff@g.batstate-u.edu.ph',
        'name' => 'RGO Staff',
        'password_hash' => '$2y$10$vCbFRAWJyVeIST4Of78FN.t3R90nWloZog/iYKf71q/1qVh2qPKI.',
    ],
];

$campuses = [
    'Lipa',
];
