<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Runtime;

use Isterkh\QueryBuilder\Contracts\QueryInterface;

class ExecutableQuery
{
    public function __construct(
        QueryInterface $query,
    )
    {
    }
}