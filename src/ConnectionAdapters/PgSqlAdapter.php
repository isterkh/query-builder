<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\ConnectionAdapters;

use PDO;

class PgSqlAdapter extends AbstractAdapter
{
    public function connect(): PDO
    {
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;',
            $this->config->host,
            $this->config->port,
            $this->config->database
        );

        return new PDO($dsn, $this->config->username, $this->config->password, $this->config->options);
    }
}