<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Traits;

use Isterkh\QueryBuilder\Clauses\HavingClause;
use Isterkh\QueryBuilder\Clauses\JoinClause;
use Isterkh\QueryBuilder\Clauses\UnionClause;
use Isterkh\QueryBuilder\Clauses\WithClause;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Enum\JoinTypeEnum;
use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;
use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\ValueObjects\TableReference;

trait QueryComponentsTrait
{
    protected ?WithClause $cte = null;

    /**
     * @var JoinClause[]
     */
    protected array $joins = [];

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

    public function with(string $alias, \Closure $callback): static
    {
        $this->getOrCreateCte()->add($alias, $callback($this->newInstance()));

        return $this;
    }

    public function join(string $table, \Closure $condition, ?string $alias = null, JoinTypeEnum $type = JoinTypeEnum::INNER): static
    {
        $joinClause = new JoinClause(
            new TableReference($table, $alias),
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
}