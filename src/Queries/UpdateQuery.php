<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Queries;

use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Expressions\Expression;

class UpdateQuery implements QueryInterface
{

    protected ?Expression $compiledQuery = null;

    public function __construct()
    {
    }

    public function toSql(): ?string
    {
        // TODO: Implement toSql() method.
    }

    public function getBindings(): array
    {
        // TODO: Implement getBindings() method.
    }

    public function setConnection(?ConnectionInterface $connection = null): static
    {
        // TODO: Implement setConnection() method.
    }

    public function getConnection(): ?ConnectionInterface
    {
        // TODO: Implement getConnection() method.
    }

    protected function getCompiled(): ?Expression
    {
        return $this->compiledQuery ??= $this->getConnection()?->getCompiled($this);
    }
}