<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Queries;

use Isterkh\QueryBuilder\Contracts\HasWhereInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\Traits\HasWhereTrait;
use Isterkh\QueryBuilder\Traits\QueryConnectionTrait;
use Isterkh\QueryBuilder\Traits\WhereAliasTrait;
use Isterkh\QueryBuilder\ValueObjects\TableReference;

class DeleteQuery implements QueryInterface, HasWhereInterface
{
    use QueryConnectionTrait;
    use HasWhereTrait;
    use WhereAliasTrait;

    protected ?Expression $compiledQuery = null;

    /**
     * @var array<mixed>
     */
    protected array $values = [];

    public function __construct(
        protected TableReference $table,
    ) {}

    public function getTable(): TableReference
    {
        return $this->table;
    }
}
