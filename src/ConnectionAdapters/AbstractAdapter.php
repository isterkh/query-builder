<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\ConnectionAdapters;

use Isterkh\QueryBuilder\Config;
use PDO;

abstract class AbstractAdapter
{
    public function __construct(
        protected Config $config
    )
    {
    }

    abstract public function connect(): PDO;
}