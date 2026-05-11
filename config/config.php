<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'clubit_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'CLB IT');
define('BASE_URL', '/clbIT');
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
