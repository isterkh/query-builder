<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Contracts;

interface LazyQueryInterface extends QueryInterface
{
    public function isLazy(): bool;
}
