<?php
namespace App\Services;

class Database {
    private static ?\PDO $instance = null;

    public static function getInstance(): \PDO {
        if (self::$instance === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $name = env('DB_DATABASE', 'smartcity');
            $user = env('DB_USERNAME', 'root');
            $pass = env('DB_PASSWORD', '');

            self::$instance = new \PDO(
                "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
                $user,
                $pass,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }
        return self::$instance;
    }
}