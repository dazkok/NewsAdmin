<?php

namespace App\Services;

use App\Config;
use PDO;

class Database
{
    private PDO $pdo;

    public function __construct(Config $cfg)
    {
        $host = (string)$cfg->get('DB_HOST', '127.0.0.1');
        $name = (string)$cfg->get('DB_NAME', '');
        $user = (string)$cfg->get('DB_USER', '');
        $pass = (string)$cfg->get('DB_PASS', '');
        $charset = (string)$cfg->get('DB_CHARSET', 'utf8mb4');
        $port = $cfg->get('DB_PORT', null);
        $socket = $cfg->get('DB_SOCKET', null);

        if (!empty($socket)) {
            $dsn = "mysql:unix_socket={$socket};dbname={$name};charset={$charset}";
        } else {
            $dsn = "mysql:host={$host}" . ($port ? ";port={$port}" : "") . ";dbname={$name};charset={$charset}";
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new PDO($dsn, $user, $pass, $options);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetch(string $sql, array $params = []): array|false
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
