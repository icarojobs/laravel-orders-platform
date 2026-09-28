<?php

declare(strict_types=1);

namespace NotificationService;

use PDO;

final class Database
{
    /**
     * The service owns its database; create it on first boot.
     */
    public static function connect(Config $config): PDO
    {
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $dsn = fn (string $database) => "pgsql:host={$config->dbHost};port={$config->dbPort};dbname={$database}";

        $admin = new PDO($dsn('postgres'), $config->dbUser, $config->dbPassword, $options);
        $exists = $admin->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
        $exists->execute([$config->dbName]);

        if ($exists->fetchColumn() === false) {
            $admin->exec('CREATE DATABASE "'.str_replace('"', '', $config->dbName).'"');
        }

        return new PDO($dsn($config->dbName), $config->dbUser, $config->dbPassword, $options);
    }
}
