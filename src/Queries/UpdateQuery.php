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

class UpdateQuery implements QueryInterface, HasWhereInterface
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

    /**
     * @param array<string, mixed>|string $columnOrArray
     *
     * @return $this
     */
    public function set(array|string $columnOrArray, mixed $value = null): static
    {
        if (is_array($columnOrArray)) {
            foreach ($columnOrArray as $column => $val) {
                $this->setSingle($column, $val);
            }
        } else {
            $this->setSingle($columnOrArray, $value);
        }

        return $this;
    }

    /**
     * @param mixed[] $bindings
     */
    public function setRaw(string $sql, array $bindings = []): static
    {
        $this->values[] = new Expression($sql, $bindings);

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    protected function setSingle(string $column, mixed $value = null): static
    {
        $this->values[$column] = $value;

        return $this;
    }
}
