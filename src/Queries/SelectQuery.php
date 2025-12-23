<?php
declare(strict_types=1);

namespace Isterkh\QueryBuilder\Queries;

use Closure;
use InvalidArgumentException;
use Isterkh\QueryBuilder\Clauses\FromClause;
use Isterkh\QueryBuilder\Clauses\HavingClause;
use Isterkh\QueryBuilder\Clauses\JoinClause;
use Isterkh\QueryBuilder\Clauses\UnionClause;
use Isterkh\QueryBuilder\Clauses\WhereClause;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Enum\JoinTypeEnum;
use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\Traits\WhereAliasTrait;
use RuntimeException;

class SelectQuery implements QueryInterface
{

    use WhereAliasTrait;
    protected FromClause $from;
    protected ?WhereClause $where = null;
    protected ?HavingClause $having = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    /**
     * @var array<string, string> $orderBy
     */
    protected array $orderBy = [];
    /**
     * @var array<int, string>
     */
    protected array $groupBy = [];

    protected ?Expression $compiledQuery = null;

    protected array $joins = [];

    /**
     * @var array<int, UnionClause>
     */
    protected array $unions = [];

    protected bool $isDistinct = false;

    protected array $columns = [];


    /**
     * @param CompilerInterface $compiler
     * @param array $columns
     * @param array<string, QueryInterface> $cte
     */
    public function __construct(
        protected CompilerInterface $compiler,
        protected array $cte = []
    )
    {
    }

    protected function newInstance(): static
    {
        return new static($this->compiler);
    }

    public function select(array|string ...$columns): static
    {
        $this->columns = $this->normalizeColumns($columns);
        return $this;
    }
    public function selectRaw(string $sql, array $bindings = []): static
    {
        $this->columns[] = new Expression($sql, $bindings);
        return $this;
    }

    protected function normalizeColumns(array $columns): array
    {
        if (empty($columns)) {
            return ['*'];
        }
        if (count($columns) === 1 && is_array($columns[0])) {
            return $columns[0];
        }
        foreach ($columns as $key => $column) {
            if (!is_string($column) || !(is_int($key) || is_string($key))) {
                throw new InvalidArgumentException('Wrong argument');
            }
        }
        return $columns;
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

    public function join(string $table, Closure $condition, ?string $alias = null, JoinTypeEnum $type = JoinTypeEnum::INNER): static
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

    public function leftJoin(string $table, Closure $condition, ?string $alias = null): static
    {
        return $this->join($table, $condition, $alias, JoinTypeEnum::LEFT);
    }

    public function rightJoin(string $table, Closure $condition, ?string $alias = null): static
    {
        return $this->join($table, $condition, $alias, JoinTypeEnum::RIGHT);
    }


    public function where(
        string|Closure $column,
        mixed          $operatorOrValue = null,
        mixed          $value = null,
    ): static
    {
        $this->getOrCreateWhere()->where($column, $operatorOrValue, $value);
        return $this;
    }

    public function orWhere(
        string|Closure $column,
        mixed          $operatorOrValue = null,
        mixed          $value = null,
    ): static
    {
        $this->getOrCreateWhere()->orWhere($column, $operatorOrValue, $value);
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->getOrCreateWhere()->whereRaw($sql, $bindings);
        return $this;
    }
    public function groupBy(string ...$column): static
    {
        $this->groupBy = array_unique(
            array_merge($this->groupBy, $column),
        );
        return $this;
    }

    public function having(
        string|Closure $column,
        mixed          $operatorOrValue = null,
        mixed          $value = null,
    ): static
    {
        $this->getOrCreateHaving()->having($column, $operatorOrValue, $value);

        return $this;
    }
    public function havingRaw(string $sql, array $bindings = []): static
    {
        $this->getOrCreateHaving()->havingRaw($sql, $bindings);
        return $this;
    }

    public function orHaving(
        string|Closure $column,
        mixed          $operatorOrValue = null,
        mixed          $value = null,
    ): static
    {
        $this->getOrCreateHaving()->orHaving($column, $operatorOrValue, $value);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $dir = strtolower($direction);
        if (!in_array($dir, ['asc', 'desc'])) {
            throw new RuntimeException("Invalid direction [$dir]");
        }
        $this->orderBy[$column] = $dir;
        return $this;
    }

    public function limit(int $limit): static
    {
        if ($limit < 0) {
            throw new RuntimeException('Limit should be greater than 0');
        }
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        if ($offset < 0) {
            throw new RuntimeException('Offset should be greater than 0');
        }
        $this->offset = $offset;
        return $this;
    }

    public function union(Closure $callback, bool $isAll = false): static
    {
        $union = $this->newInstance();
        $callback($union);
        $this->unions[] = new UnionClause($union, $isAll);
        return $this;
    }

    public function unionAll(Closure $callback): static
    {
        return $this->union($callback, true);
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFrom(): FromClause
    {
        return $this->from;
    }

    public function getWhere(): ?WhereClause
    {
        return $this->where;
    }

    /**
     * @return array<JoinClause>
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

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }


    public function toSql(): string
    {
        return $this->getCompiled()->sql;
    }

    public function getBindings(): array
    {
        return $this->getCompiled()->bindings;
    }


    protected function getOrCreateWhere(): WhereClause
    {
        return $this->where ??= new WhereClause(
            new ConditionGroup()
        );
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

    protected function getCompiled(): Expression
    {
        return $this->compiledQuery ??= $this->compiler->compile($this);
    }

    public function getCte(): array
    {
        return $this->cte;
    }

    public function getUnions(): array
    {
        return $this->unions;
    }

}