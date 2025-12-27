<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder;

class Config
{
    public function __construct(
        public string $database,
        public string $driver = 'mysql',
        public string $username = '',
        public string $password = '',
        public string $host = 'localhost',
        public int    $port = 3306,
        public string $charset = 'utf8mb4',
        public string $collation = 'utf8mb4_unicode_ci',
        public string $prefix = '',
        public array  $options = []
    )
    {
    }

    public static function fromArray(array $config): self
    {
        return new self(
            database: $config['database'] ?? null,
            driver: $config['driver'] ?? 'mysql',
            username: $config['username'] ?? '',
            password: $config['password'] ?? '',
            host: $config['host'] ?? 'localhost',
            port: $config['port'] ?? 3306,
            charset: $config['charset'] ?? 'utf8mb4',
            collation: $config['collation'] ?? 'utf8mb4_unicode_ci',
            prefix: $config['prefix'] ?? '',
            options: $config['options'] ?? []
        );
    }
}