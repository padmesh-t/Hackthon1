<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'traveloop');
define('DB_USER', 'root');
define('DB_PASS', '');

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        try {
            $pdo2 = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo2->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e2) {
            die("<div style='font:16px monospace;color:#f55;padding:40px'>DB Error: ".$e2->getMessage()."<br>Please create a MySQL database named <b>".DB_NAME."</b> and update DB_USER/DB_PASS in config/database.php.</div>");
        }
    }
    return $pdo;
}