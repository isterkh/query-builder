<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\ConnectionAdapters;

use Isterkh\QueryBuilder\Config;
use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;

class ConnectionAdapterFactory
{
    protected static $handlers = [
        'mysql' => MySqlAdapter::class,
        'pgsql' => PgSqlAdapter::class,
    ];

    public static function make(Config $config): AbstractAdapter
    {
        $class = static::$handlers[$config->driver] ?? null;
        if ($class === null || !class_exists($class)) {
            throw new QueryBuilderException("Unsupported driver '{$config->driver}'");
        }
        return new $class($config);
    }

    public static function addHandler(string $driver, string $adapter): void
    {
        if (!is_subclass_of($adapter, AbstractAdapter::class)) {
            throw new QueryBuilderException('Adapter must extend AbstractAdapter class');
        }
        static::$handlers[$driver] = $adapter;
    }
}