<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers;

use Isterkh\QueryBuilder\Compilers\Traits\BasicCompilerTrait;
use Isterkh\QueryBuilder\Compilers\Traits\CompilesConditionsTrait;
use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Components\JoinClause;
use Isterkh\QueryBuilder\Components\TableReference;
use Isterkh\QueryBuilder\Components\UnionClause;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\Queries\SelectQuery;
use Isterkh\QueryBuilder\QueryBuilder;

class SqlCompiler implements CompilerInterface
{
    use BasicCompilerTrait;

    protected ConditionsCompiler $conditionsCompiler;
    public function __construct()
    {
        $this->conditionsCompiler = new ConditionsCompiler();
    }

    public function supports(QueryInterface $query): bool
    {
        return true;
    }

    /**
     * @param SelectQuery $query
     */
    public function compile(QueryBuilder $query): Expression
    {
        $cte = $this->compileCte($query);

        $main = $this->makeExpression([
            $this->compileSelect($query),
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

    protected function compileTable(TableReference $from): string
    {
        $table = $this->wrap($from->getTable());
        if (!empty($from->getAlias())) {
            $table .= (' as ' . $this->wrap($from->getAlias()));
        }

        return $table;
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

    protected function compileSelect(QueryBuilder $query): ?Expression
    {
        if (empty($query->getTable())) {
            throw new CompilerException('Missing from clause');
        }

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

    protected function compileWhere(QueryBuilder $query): ?Expression
    {
        if (empty($query->getWhere())) {
            return null;
        }

        return $this->conditionsCompiler->compile($query->getWhere()->getConditions())
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
        $conditions = $this->conditionsCompiler->compile($join->getConditions());
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

        return $this->conditionsCompiler->compile($query->getHaving()->getConditions())->prefix('having ');
    }

    protected function compileLimit(QueryBuilder $query, bool $union = false): ?Expression
    {
        $limit = $union ? $query->getUnionLimit() : $query->getLimit();
        if (null === $limit) {
            return null;
        }

        return $this->makeExpression("limit {$limit}");
    }

    protected function compileOffset(QueryBuilder $query, bool $union = false): ?Expression
    {
        $offset = $union ? $query->getUnionOffset() : $query->getOffset();
        if (null === $offset || $offset <= 0) {
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
}
