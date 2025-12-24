<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Contracts;

interface QueryInterface
{
    public function toSql(): ?string;
    public function getBindings(): array;
}