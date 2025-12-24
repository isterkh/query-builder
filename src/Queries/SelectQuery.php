<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Queries;

use Isterkh\QueryBuilder\Clauses\FromClause;
use Isterkh\QueryBuilder\Clauses\HavingClause;
use Isterkh\QueryBuilder\Clauses\JoinClause;
use Isterkh\QueryBuilder\Clauses\UnionClause;
use Isterkh\QueryBuilder\Clauses\WhereClause;
use Isterkh\QueryBuilder\Clauses\WithClause;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\Contracts\LazyQueryInterface;
use Isterkh\QueryBuilder\Enum\JoinTypeEnum;
use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;
use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\Traits\WhereAliasTrait;

class SelectQuery implements LazyQueryInterface
{
    use WhereAliasTrait;

    protected ?FromClause $from = null;

    /**
     * @var Expression[]|string[]
     */
    protected array $columns = [];
    protected bool $isDistinct = false;

    /**
     * @var JoinClause[]
     */
    protected array $joins = [];
    protected ?WhereClause $where = null;

    /**
     * @var array<int|string, Expression|string>
     */
    protected array $groupBy = [];
    protected ?HavingClause $having = null;

    /**
     * @var array<int|string, Expression|string>
     */
    protected array $orderBy = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    /**
     * @var array<int, UnionClause>
     */
    protected array $unions = [];

    /**
     * @var array<int|string, Expression|string>
     */
    protected array $unionOrderBy = [];
    protected ?int $unionLimit = null;
    protected ?int $unionOffset = null;
    protected ?Expression $compiledQuery = null;
    protected ?ConnectionInterface $connection = null;
    protected bool $lazy = false;

    public function __construct(
        protected ?WithClause $cte = null
    ) {}

    public function setConnection(?ConnectionInterface $connection = null): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param array<int|string, int|string>|string $columns
     */
    public function select(array|string ...$columns): static
    {
        $this->columns = $this->normalizeColumns($columns);

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function selectRaw(string $sql, array $bindings = []): static
    {
        $this->columns[] = new Expression($sql, $bindings);

        return $this;
    }

    public function distinct(): static
    {
        $this->isDistinct = true;

        return $this;
    }

    public function from(string $table, ?string $alias = null): static
    {
        $this->from = new FromClause($table, $alias);

        return $this;
    }

    public function join(string $table, \Closure $condition, ?string $alias = null, JoinTypeEnum $type = JoinTypeEnum::INNER): static
    {
        $joinClause = new JoinClause(
            new FromClause($table, $alias),
            $type,
            new ConditionGroup()
        );
        $condition($joinClause);
        $this->joins[] = $joinClause;

        return $this;
    }

    public function leftJoin(string $table, \Closure $condition, ?string $alias = null): static
    {
        return $this->join($table, $condition, $alias, JoinTypeEnum::LEFT);
    }

    public function rightJoin(string $table, \Closure $condition, ?string $alias = null): static
    {
        return $this->join($table, $condition, $alias, JoinTypeEnum::RIGHT);
    }

    public function where(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
    ): static {
        $this->getOrCreateWhere()->where($column, $operatorOrValue, $value);

        return $this;
    }

    public function orWhere(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
    ): static {
        $this->getOrCreateWhere()->orWhere($column, $operatorOrValue, $value);

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->getOrCreateWhere()->whereRaw($sql, $bindings);

        return $this;
    }

    public function groupBy(string ...$columns): static
    {
        foreach ($columns as $column) {
            $this->groupBy[$column] = $column;
        }

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function groupByRaw(string $sql, array $bindings = []): static
    {
        $this->groupBy[] = new Expression($sql, $bindings);

        return $this;
    }

    public function having(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
    ): static {
        $this->getOrCreateHaving()->having($column, $operatorOrValue, $value);

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function havingRaw(string $sql, array $bindings = []): static
    {
        $this->getOrCreateHaving()->havingRaw($sql, $bindings);

        return $this;
    }

    public function orHaving(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
    ): static {
        $this->getOrCreateHaving()->orHaving($column, $operatorOrValue, $value);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $dir = strtolower($direction);
        if (!in_array($dir, ['asc', 'desc'])) {
            throw new \RuntimeException("Invalid direction [{$dir}]");
        }
        $param = empty($this->unions) ? 'orderBy' : 'unionOrderBy';
        $this->{$param}[$column] = $dir;

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function orderByRaw(string $sql, array $bindings = []): static
    {
        $param = empty($this->unions) ? 'orderBy' : 'unionOrderBy';
        $this->{$param}[] = new Expression($sql, $bindings);

        return $this;
    }

    public function limit(int $limit): static
    {
        if ($limit < 0) {
            throw new QueryBuilderException('Limit should be greater than 0');
        }
        $param = empty($this->unions) ? 'limit' : 'unionLimit';

        $this->{$param} = $limit;

        return $this;
    }

    public function offset(int $offset): static
    {
        if ($offset < 0) {
            throw new QueryBuilderException('Offset should be greater than 0');
        }
        $param = empty($this->unions) ? 'offset' : 'unionOffset';
        $this->{$param} = $offset;

        return $this;
    }

    public function union(\Closure $callback, bool $isAll = false): static
    {
        $union = $this->newInstance();
        $callback($union);
        $this->unions[] = new UnionClause($union, $isAll);

        return $this;
    }

    public function unionAll(\Closure $callback): static
    {
        return $this->union($callback, true);
    }

    /**
     * @return Expression[]|string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFrom(): ?FromClause
    {
        return $this->from;
    }

    public function getWhere(): ?WhereClause
    {
        return $this->where;
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

    public function getOrCreateHaving(): HavingClause
    {
        return $this->having ??= new HavingClause(
            new ConditionGroup()
        );
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
            return ['*'];
        }
        $result = [];
        foreach ($columns as $column) {
            if (empty($column)) {
                $result[] = '*';

                continue;
            }
            if (is_string($column)) {
                $result[] = trim($column) ?: '*';

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
                    $index = trim($index) ?: '*';
                    $result[] = "{$index} as {$columnOrAlias}";
                }
            }
        }

        return $result;
    }

    protected function getOrCreateWhere(): WhereClause
    {
        return $this->where ??= new WhereClause(
            new ConditionGroup()
        );
    }

    protected function getCompiled(): ?Expression
    {
        return $this->compiledQuery ??= $this->connection?->getCompiled($this);
    }
}
