<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('SITE_NAME', 'Vidyasagar Academy');
define('BASE_URL', 'https://vsa.edu.in');
define('ADMIN_PATH', '/admin');
define('TIMEZONE', 'Asia/Kolkata');
define('UPLOAD_MAX_MB', 5);

date_default_timezone_set(TIMEZONE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
