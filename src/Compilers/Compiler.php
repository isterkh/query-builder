<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers;

use Isterkh\QueryBuilder\Compilers\Traits\CompilesConditionsTrait;
use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Components\ExpressionBuilder;
use Isterkh\QueryBuilder\Components\JoinClause;
use Isterkh\QueryBuilder\Components\TableReference;
use Isterkh\QueryBuilder\Components\UnionClause;
use Isterkh\QueryBuilder\Contracts\GrammarInterface;
use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\QueryBuilder;

class Compiler
{
    use CompilesConditionsTrait;

    public function __construct(
        protected GrammarInterface $grammar,
    ) {}

    public function compile(QueryBuilder $query): Expression
    {
        $method = 'compile' . ucfirst($query->getType()->value);
        if (!method_exists($this, $method)) {
            throw new CompilerException('Cannot compile query: ' . $query->getType()->value);
        }

        return $this->{$method}($query);
    }

    /**
     * @param null|mixed[]|string $source
     */
    public function makeExpression(
        array|string|null $source,
        string $separator = ' ',
        ?\Closure $formatted = null,
        ?string $prefix = null,
        ?string $suffix = null
    ): Expression {
        if (empty($source)) {
            return new Expression('');
        }

        $builder = new ExpressionBuilder($prefix, $suffix, $separator);

        if (is_string($source)) {
            return $builder->add($source)->get();
        }

        foreach ($source as $key => $item) {
            if (empty($item)) {
                continue;
            }
            if ($item instanceof Expression) {
                $builder->add($item->getSql(), $item->getBindings());

                continue;
            }
            if ($formatted) {
                @[$sql, $binds] = $formatted($key, $item);
                $builder->add($sql ?: '', $binds ?? []);

                continue;
            }
            $item = is_array($item) ? $item : [$item];
            if (is_string($key)) {
                $builder->add("{$key} = ?", $item);

                continue;
            }
            $builder->add("{$key}", $item);
        }

        return $builder->get();
    }

    protected function compileSelect(QueryBuilder $query): Expression
    {
        $cte = $this->compileCte($query);

        $main = $this->makeExpression([
            $this->compileSelectStatement($query),
            $this->compileJoins($query),
            $this->compileWhere($query),
            $this->compileGroupBy($query),
            $this->compileHaving($query),
            $this->compileOrderBy($query),
            $this->compileLimit($query),
            $this->compileOffset($query),
        ]);

        $unions = $this->compileUnions($query);

        if ($unions) {
            $main = $main->wrap();

            $unions = $unions->merge(
                $this->makeExpression([
                    $this->compileOrderBy($query, true),
                    $this->compileLimit($query, true),
                    $this->compileOffset($query, true),
                ])
            );
        }

        return $this->makeExpression([$cte, $main, $unions]);
    }

    protected function compileUpdate(QueryBuilder $query): Expression
    {
        return $this->makeExpression([
            $this->compileCte($query),
            $this->compileUpdateStatement($query),
            $this->compileWhere($query),
        ]);
    }

    protected function compileDelete(QueryBuilder $query): Expression
    {

        $table = $this->compileTable($query->getTable());

        return $this->makeExpression([
            $this->compileCte($query),
            new Expression("delete from {$table}"),
            $this->compileWhere($query),
        ]);
    }

    protected function compileInsert(QueryBuilder $query): Expression
    {
        $columns = implode(', ', array_map(fn ($item) => $this->wrap($item), array_keys($query->getInsertValues())));

        $table = $this->compileTable($query->getTable());
        $prefix = "insert into {$table} ({$columns}) values (";
        $suffix = ')';

        return $this->makeExpression(
            source: $query->getInsertValues(),
            separator: ', ',
            formatted: fn ($key, $item) => ['?', [$item]],
            prefix: $prefix,
            suffix: $suffix,
        );
    }

    protected function compileRaw(QueryBuilder $query): Expression
    {
        return $query->getRaw() ?? new Expression('');
    }

    protected function compileTable(?TableReference $table): string
    {
        if ($table === null) {
            throw new CompilerException('Missing from clause');
        }
        $result = $this->wrap($table->getTable());
        if (!empty($table->getAlias())) {
            $result .= (' as ' . $this->wrap($table->getAlias()));
        }

        return $result;
    }

    protected function compileUnions(QueryBuilder $query): ?Expression
    {
        if (empty($query->getUnions())) {
            return null;
        }

        return $this->makeExpression(
            source: $query->getUnions(),
            formatted: fn (int $key, UnionClause $item) => [
                $this->unionClauseToSql($item),
                $item->getQuery()->getBindings(),
            ]
        );
    }

    protected function unionClauseToSql(UnionClause $union): string
    {
        $operator = $union->isAll() ? 'union all' : 'union';

        return "{$operator} ({$union->getQuery()->toSql()})";
    }

    protected function compileCte(QueryBuilder $query): ?Expression
    {
        return $this->makeExpression(
            source: $query->getCte()?->getQueries(),
            separator: ', ',
            formatted: fn (string $alias, QueryBuilder $query) => [
                "{$this->wrap($alias)} as ({$query->toSql()})",
                $query->getBindings(),
            ],
            prefix: 'with '
        );
    }

    protected function compileSelectStatement(QueryBuilder $query): ?Expression
    {
        $prefix = 'select '
            . ($query->isDistinct() ? 'distinct ' : '');
        $suffix = ' from ' . $this->compileTable($query->getTable());

        return $this->makeExpression(
            source: empty($query->getColumns()) ? '*' : $query->getColumns(),
            separator: ', ',
            formatted: fn ($key, $item) => [$this->wrap(is_string($key) ? "{$key} as {$item}" : "{$item}")],
            prefix: $prefix,
            suffix: $suffix,
        );
    }

    protected function compileUpdateStatement(QueryBuilder $query): ?Expression
    {
        $prefix = 'update ' . $this->compileTable($query->getTable()) . ' set ';

        return $this->makeExpression(
            source: $query->getUpdateValues(),
            separator: ', ',
            formatted: fn ($key, $item) => ["{$this->wrap($key)} = ?", [$item]],
            prefix: $prefix
        );
    }

    protected function compileWhere(QueryBuilder $query): ?Expression
    {
        if (empty($query->getWhere())) {
            return null;
        }

        return $this->compileConditions($query->getWhere()->getConditions())
            ->prefix('where ')
        ;
    }

    protected function compileJoins(QueryBuilder $query): ?Expression
    {
        return $this->makeExpression(
            source: $query->getJoins(),
            formatted: fn (int $key, JoinClause $join) => $this->joinToArray($join)
        );
    }

    /**
     * @return array<mixed[]|string>
     */
    protected function joinToArray(JoinClause $join): array
    {
        $conditions = $this->compileConditions($join->getConditions());
        $type = $join->getType()->value;
        $table = $this->compileTable($join->getFrom());
        $sql = "{$type} join {$table}";
        if (empty($conditions->getSql())) {
            return [$sql];
        }

        return ["{$sql} on {$conditions->getSql()}", $conditions->getBindings()];
    }

    protected function compileHaving(QueryBuilder $query): ?Expression
    {
        if (empty($query->getHaving())) {
            return null;
        }

        return $this->compileConditions($query->getHaving()->getConditions())->prefix('having ');
    }

    protected function compileLimit(QueryBuilder $query, bool $union = false): ?Expression
    {
        $limit = $union ? $query->getUnionLimit() : $query->getLimit();
        if ($limit === null) {
            return null;
        }

        return $this->makeExpression("limit {$limit}");
    }

    protected function compileOffset(QueryBuilder $query, bool $union = false): ?Expression
    {
        $offset = $union ? $query->getUnionOffset() : $query->getOffset();
        if ($offset === null || $offset <= 0) {
            return null;
        }

        return $this->makeExpression("offset {$offset}");
    }

    protected function compileOrderBy(QueryBuilder $query, bool $union = false): ?Expression
    {
        $orderBy = $union ? $query->getUnionOrderBy() : $query->getOrderBy();

        return $this->makeExpression(
            source: $orderBy,
            separator: ', ',
            formatted: fn ($key, $dir) => ["{$this->wrap($key)} {$dir}"],
            prefix: 'order by ',
        );
    }

    protected function compileGroupBy(QueryBuilder $query): ?Expression
    {
        return $this->makeExpression(
            source: $query->getGroupBy(),
            separator: ', ',
            formatted: fn ($key, $dir) => ["{$this->wrap($key)}"],
            prefix: 'group by ',
        );
    }

    protected function wrap(int|string $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        $split = preg_split('/\s+(as)\s+/i', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        if ($split === false) {
            return $this->grammar->wrap($value);
        }
        $split = array_slice($split, 0, 3);
        $ignore = ['*', 'as', 'AS'];
        $parts = [];
        foreach ($split as $part) {
            if (in_array($part, $ignore)) {
                $parts[] = $part;

                continue;
            }
            $parts[] = implode('.', array_map(fn ($p) => $this->grammar->wrap($p), explode('.', $part)));
        }

        return implode(' ', $parts);
    }
}
