<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compilers\Grammar;

use Isterkh\QueryBuilder\Config;
use Isterkh\QueryBuilder\ConnectionAdapters\AbstractAdapter;
use Isterkh\QueryBuilder\Contracts\GrammarInterface;
use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;

class GrammarFactory
{
    protected static $handlers = [
        'mysql' => MySqlGrammar::class,
        'pgsql' => PgSqlGrammar::class,
        'sqlite' => SqLiteGrammar::class,
    ];

    public static function make(string $driver): GrammarInterface
    {
        $class = static::$handlers[$driver] ?? null;
        if ($class === null || !class_exists($class)) {
            throw new QueryBuilderException("Unsupported driver '{driver}'");
        }
        return new $class();
    }

    public static function addHandler(string $driver, string $adapter): void
    {
        if (!is_subclass_of($adapter, GrammarInterface::class)) {
            throw new QueryBuilderException('Adapter must extend GrammarInterface interface');
        }
        static::$handlers[$driver] = $adapter;
    }
}