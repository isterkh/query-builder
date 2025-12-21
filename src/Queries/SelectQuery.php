<?php
declare(strict_types=1);

namespace Isterkh\QueryBuilder\Queries;

use Closure;
use Isterkh\QueryBuilder\Clauses\FromClause;
use Isterkh\QueryBuilder\Clauses\HavingClause;
use Isterkh\QueryBuilder\Clauses\WhereClause;
use Isterkh\QueryBuilder\Compilers\DTO\CompiledQuery;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use RuntimeException;

class SelectQuery implements QueryInterface
{
    protected FromClause $from;
    protected ?WhereClause $where = null;
    protected ?HavingClause $having = null;
    protected int $limit = 0;
    protected int $offset = 0;
    /**
     * @var array<string, string> $orderBy
     */
    protected array $orderBy = [];
    /**
     * @var array<int, string>
     */
    protected array $groupBy = [];

    protected ?CompiledQuery $compiledQuery = null;


    public function __construct(
        protected CompilerInterface $compiler,
        protected array             $columns = ['*'],
    )
    {
    }

    public function from(string $table, ?string $alias = null): static
    {
        $this->from = new FromClause($table, $alias);
        return $this;
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

    public function whereIn(
        string|Closure $column,
        array          $values
    ): static
    {
        $this->getOrCreateWhere()->where($column, 'IN', $values);
        return $this;
    }

    public function whereNotIn(
        string|Closure $column,
        array          $values
    ): static
    {
        $this->getOrCreateWhere()->where($column, 'NOT IN', $values);
        return $this;
    }

    public function orWhereIn(
        string|Closure $column,
        array          $values
    ): static
    {
        $this->getOrCreateWhere()->orWhere($column, 'IN', $values);
        return $this;
    }

    public function orWhereNotIn(
        string|Closure $column,
        array          $values
    ): static
    {
        $this->getOrCreateWhere()->orWhere($column, 'NOT IN', $values);
        return $this;
    }

    public function whereBetween(string|Closure $column, int|string $value1, int|string $value2): static
    {
        $this->getOrCreateWhere()->where($column, 'BETWEEN', [$value1, $value2]);
        return $this;
    }

    public function whereNotBetween(string|Closure $column, int|string $value1, int|string $value2): static
    {
        $this->getOrCreateWhere()->where($column, 'NOT BETWEEN', [$value1, $value2]);
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

    public function orHaving(
        string|Closure $column,
        mixed          $operatorOrValue = null,
        mixed          $value = null,
    ): static
    {
        $this->getOrCreateHaving()->orHaving($column, $operatorOrValue, $value);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $dir = strtoupper($direction);
        if (!in_array($dir, ['ASC', 'DESC'])) {
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

    public function getHaving(): ?HavingClause
    {
        return $this->having;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
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
        return $this->where ??= new WhereClause();
    }

    public function getOrCreateHaving(): HavingClause
    {
        return $this->having ??= new HavingClause();
    }

    protected function getCompiled(): CompiledQuery
    {
        return $this->compiledQuery ??= $this->compiler->compile($this);
    }

}