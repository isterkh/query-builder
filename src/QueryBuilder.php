<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder;

use Isterkh\QueryBuilder\Clauses\HavingClause;
use Isterkh\QueryBuilder\Clauses\JoinClause;
use Isterkh\QueryBuilder\Clauses\UnionClause;
use Isterkh\QueryBuilder\Clauses\WithClause;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\Contracts\HasWhereInterface;
use Isterkh\QueryBuilder\Contracts\LazyQueryInterface;
use Isterkh\QueryBuilder\Enum\JoinTypeEnum;
use Isterkh\QueryBuilder\Enum\QueryTypeEnum;
use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;
use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\Traits\HasWhereTrait;
use Isterkh\QueryBuilder\Traits\QueryComponentsTrait;
use Isterkh\QueryBuilder\Traits\QueryConnectionTrait;
use Isterkh\QueryBuilder\Traits\WhereAliasTrait;
use Isterkh\QueryBuilder\ValueObjects\TableReference;

class QueryBuilder implements LazyQueryInterface, HasWhereInterface
{
    use QueryComponentsTrait;
    use HasWhereTrait;
    use QueryConnectionTrait;
    use WhereAliasTrait;

    protected ?TableReference $table = null;
    protected QueryTypeEnum $type = QueryTypeEnum::SELECT;

    /**
     * @var Expression[]|string[]
     */
    protected array $columns = [];
    protected bool $isDistinct = false;

    protected ?Expression $compiledQuery = null;
    protected bool $lazy = false;

    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection;
    }

    /**
     * @param array<int|string, int|string>|string $columns
     */
    public function select(array|string ...$columns): static
    {
        $this->columns = $this->normalizeColumns($columns);
        $this->type = QueryTypeEnum::SELECT;

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function selectRaw(string $sql, array $bindings = []): static
    {
        $this->columns[] = new Expression($sql, $bindings);
        $this->type = QueryTypeEnum::SELECT;

        return $this;
    }

    public function distinct(): static
    {
        $this->isDistinct = true;

        return $this;
    }

    public function from(string $table, ?string $alias = null): static
    {
        return $this->table($table, $alias);
    }

    public function table(string $table, ?string $alias = null): static
    {
        $this->table = new TableReference($table, $alias);

        return $this;
    }

    /**
     * @return Expression[]|string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getTable(): ?TableReference
    {
        return $this->table;
    }

    /**
     * @return JoinClause[]
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getHaving(): ?HavingClause
    {
        return $this->having;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getUnionLimit(): ?int
    {
        return $this->unionLimit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getUnionOffset(): ?int
    {
        return $this->unionOffset;
    }

    /**
     * @return Expression[]|string[]
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return Expression[]|string[]
     */
    public function getUnionOrderBy(): array
    {
        return $this->unionOrderBy;
    }

    /**
     * @return Expression[]|string[]
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function lazy(): static
    {
        $this->lazy = true;

        return $this;
    }

    public function update(): int {

    }
    public function delete(): int {}
    public function insert(): int {}

    public function getOrCreateHaving(): HavingClause
    {
        return $this->having ??= new HavingClause(
            new ConditionGroup()
        );
    }

    public function getOrCreateCte(): WithClause
    {
        return $this->cte ??= new WithClause();
    }

    public function isDistinct(): bool
    {
        return $this->isDistinct;
    }

    public function getCte(): ?WithClause
    {
        return $this->cte;
    }

    /**
     * @return UnionClause[]
     */
    public function getUnions(): array
    {
        return $this->unions;
    }

    /**
     * @return null|iterable<mixed>
     */
    public function get(): ?iterable
    {
        return $this->connection?->query($this);
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }

    protected function newInstance(): self
    {
        return new self()
            ->setConnection($this->connection)
            ;
    }

    /**
     * @param array<mixed, mixed> $columns
     *
     * @return string[]
     */
    protected function normalizeColumns(array $columns): array
    {
        if (1 === count($columns) && is_array($columns[0])) {
            $columns = $columns[0];
        }
        if (empty($columns)) {
            return [];
        }
        $result = [];
        foreach ($columns as $column) {
            if (empty($column)) {
                continue;
            }
            if (is_string($column)) {
                $result[] = trim($column);

                continue;
            }
            foreach ($column as $index => $columnOrAlias) {
                if (!is_string($columnOrAlias)
                    || (is_string($index) && empty($columnOrAlias))) {
                    throw new \InvalidArgumentException('Column must be a string or key-value array');
                }
                $columnOrAlias = trim($columnOrAlias) ?: '*';
                if (is_int($index)) {
                    $result[] = $columnOrAlias;
                } else {
                    $index = trim($index);
                    $result[] = "{$index} as {$columnOrAlias}";
                }
            }
        }

        return array_filter($result);
    }
}
