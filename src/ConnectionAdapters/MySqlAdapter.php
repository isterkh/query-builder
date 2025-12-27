<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\ConnectionAdapters;

use PDO;

class MySqlAdapter extends AbstractAdapter
{
    public function connect(): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config->host,
            $this->config->port,
            $this->config->database,
            $this->config->charset
        );

        return new PDO($dsn, $this->config->username, $this->config->password, $this->config->options);
    }
}