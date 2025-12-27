<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Traits;

use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;

trait QueryConnectionTrait
{
    protected ?ConnectionInterface $connection = null;
    protected ?Expression $compiledQuery = null;

    public function getConnection(): ?ConnectionInterface
    {
        return $this->connection;
    }

    public function setConnection(?ConnectionInterface $connection = null): static
    {
        $this->connection = $connection;

        return $this;
    }

    public function toSql(): ?string
    {
        return $this->getCompiled()?->getSql();
    }

    /**
     * @return array<mixed>
     */
    public function getBindings(): array
    {
        return $this->getCompiled()?->getBindings() ?? [];
    }

    protected function getCompiled(): ?Expression
    {
        return $this->compiledQuery ??= $this->getConnection()?->getCompiled($this);
    }
}
