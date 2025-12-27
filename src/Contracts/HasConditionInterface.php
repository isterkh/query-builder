<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Contracts;

use Isterkh\QueryBuilder\Components\ConditionGroup;

interface HasConditionInterface
{
    public function getConditions(): ConditionGroup;
}
