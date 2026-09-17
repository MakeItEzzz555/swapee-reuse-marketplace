<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'swapee');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db(): mysqli
{
    static $connection;
    if ($connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($connection->connect_errno) {
        http_response_code(500);
        die('Database connection failed: ' . $connection->connect_error);
    }

    $connection->set_charset('utf8mb4');
    return $connection;
}
