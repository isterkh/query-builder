<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder;

use Isterkh\QueryBuilder\Queries\SelectQuery;

class QueryBuilder
{
    public function select(array $columns = ['*']): SelectQuery
    {
        return new SelectQuery($columns);
    }
}