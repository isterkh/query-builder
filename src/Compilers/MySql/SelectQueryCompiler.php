<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers\MySql;

use Isterkh\QueryBuilder\Clauses\JoinClause;
use Isterkh\QueryBuilder\Clauses\UnionClause;
use Isterkh\QueryBuilder\Compilers\MySql\Traits\BasicCompilerTrait;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Exceptions\CompilerDoesNotSupportsQuery;
use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\Queries\SelectQuery;

class SelectQueryCompiler implements CompilerInterface
{
    use BasicCompilerTrait;

    public function __construct(
        protected ConditionsCompiler $conditionsCompiler
    ) {}

    public function supports(QueryInterface $query): bool
    {
        return $query instanceof SelectQuery;
    }

    /**
     * @param SelectQuery $query
     */
    public function compile(QueryInterface $query): Expression
    {
        if (!$this->supports($query)) {
            throw new CompilerDoesNotSupportsQuery();
        }

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

    protected function compileUnions(SelectQuery $query): ?Expression
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

    protected function compileCte(SelectQuery $query): ?Expression
    {
        return $this->makeExpression(
            source: $query->getCte()?->getQueries(),
            separator: ', ',
            formatted: fn (string $alias, QueryInterface $query) => [
                "{$this->wrap($alias)} as ({$query->toSql()})",
                $query->getBindings(),
            ],
            prefix: 'with '
        );
    }

    protected function compileSelect(SelectQuery $query): ?Expression
    {
        if (empty($query->getFrom())) {
            throw new CompilerException('Missing from clause');
        }

        $prefix = 'select '
            . ($query->isDistinct() ? 'distinct ' : '');
        $suffix = ' from ' . $this->compileTable($query->getFrom());

        return $this->makeExpression(
            source: $query->getColumns(),
            separator: ', ',
            formatted: fn ($key, $item) => [$this->wrap(is_string($key) ? "{$key} as {$item}" : "{$item}")],
            prefix: $prefix,
            suffix: $suffix,
        );
    }

    protected function compileWhere(SelectQuery $query): ?Expression
    {
        if (empty($query->getWhere())) {
            return null;
        }

        return $this->conditionsCompiler->compile($query->getWhere()->getConditions())
            ->prefix('where ')
        ;
    }

    protected function compileJoins(SelectQuery $query): ?Expression
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

    protected function compileHaving(SelectQuery $query): ?Expression
    {
        if (empty($query->getHaving())) {
            return null;
        }

        return $this->conditionsCompiler->compile($query->getHaving()->getConditions())->prefix('having ');
    }

    protected function compileLimit(SelectQuery $query, bool $union = false): ?Expression
    {
        $limit = $union ? $query->getUnionLimit() : $query->getLimit();
        if (null === $limit) {
            return null;
        }

        return $this->makeExpression("limit {$limit}");
    }

    protected function compileOffset(SelectQuery $query, bool $union = false): ?Expression
    {
        $offset = $union ? $query->getUnionOffset() : $query->getOffset();
        if (null === $offset || $offset <= 0) {
            return null;
        }

        return $this->makeExpression("offset {$offset}");
    }

    protected function compileOrderBy(SelectQuery $query, bool $union = false): ?Expression
    {
        $orderBy = $union ? $query->getUnionOrderBy() : $query->getOrderBy();

        return $this->makeExpression(
            source: $orderBy,
            separator: ', ',
            formatted: fn ($key, $dir) => ["{$this->wrap($key)} {$dir}"],
            prefix: 'order by ',
        );
    }

    protected function compileGroupBy(SelectQuery $query): ?Expression
    {
        return $this->makeExpression(
            source: $query->getGroupBy(),
            separator: ', ',
            formatted: fn ($key, $dir) => ["{$this->wrap($key)}"],
            prefix: 'group by ',
        );
    }
}
