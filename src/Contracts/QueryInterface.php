<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Contracts;

interface QueryInterface
{
    public function toSql(): ?string;

    /**
     * @return mixed[]
     */
    public function getBindings(): array;

    public function setConnection(?ConnectionInterface $connection = null): static;

    public function getConnection(): ?ConnectionInterface;
}
