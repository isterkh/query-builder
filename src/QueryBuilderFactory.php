<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder;
use Isterkh\QueryBuilder\Compilers\Compiler;
use Isterkh\QueryBuilder\Compilers\Grammar\GrammarFactory;
use Isterkh\QueryBuilder\ConnectionAdapters\ConnectionAdapterFactory;
use PDO;

class QueryBuilderFactory
{
    public static function make(array|Config $config): QueryBuilder
    {
        $config = is_array($config)
            ? Config::fromArray($config)
            : $config;

        $pdo = ConnectionAdapterFactory::make($config)->connect();
        $compiler = new Compiler(GrammarFactory::make($config->driver));
        $connection = new Connection($compiler, $pdo);

        return new QueryBuilder($connection);

    }
}