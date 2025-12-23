<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compilers\MySql;

use Isterkh\QueryBuilder\Clauses\FromClause;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Exceptions\CompilerDoesNotSupportsQuery;
use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\Queries\SelectQuery;
use Isterkh\QueryBuilder\Traits\WrapColumnsTrait;


class SelectQueryCompiler implements CompilerInterface
{

    use WrapColumnsTrait;

    public function __construct(
        protected ConditionsCompiler $conditionsCompiler
    )
    {
    }

    public function supports(QueryInterface $query): bool
    {
        return $query instanceof SelectQuery;
    }

    /**
     * @param SelectQuery $query
     * @return Expression
     */
    public function compile(QueryInterface $query): Expression
    {

        if (!$this->supports($query)) {
            throw new CompilerDoesNotSupportsQuery();
        }

        $cte = $this->compileCte($query);

        $main = $this->buildExpression([
            $this->compileSelect($query),
            $this->compileJoins($query),
            $this->compileWhere($query),
            $this->compileGroupBy($query),
            $this->compileHaving($query),
            $this->compileOrderBy($query),
            $this->compileLimit($query),
        ]);

        $unions = $this->compileUnions($query);

        if ($unions) {
            $main = $main->wrap();

            $unions = $unions->merge(
                $this->buildExpression([
                    $this->compileLimit($query, true),
                    $this->compileOffset($query, true),
                    $this->compileOrderBy($query, true)
                ])
            );
        }
        return $this->buildExpression([$cte, $main, $unions]);
    }

    protected function buildExpression(array $expressions): Expression
    {
        $filtered = array_filter($expressions);
        if (empty($filtered)) {
            return new Expression('');
        }
        return Expression::fromExpressions(...$filtered);
    }

    protected function compileUnions(SelectQuery $query): ?Expression
    {
        if (empty($query->getUnions())) {
            return null;
        }
        $parts = [];
        $bindings = [];
        foreach ($query->getUnions() as $union) {
            $operator = $union->isAll() ? 'union all' : 'union';
            $parts[] = "{$operator} ({$union->getQuery()->toSql()})";
            $bindings = array_merge($bindings, $union->getQuery()->getBindings());
        }
        return new Expression(implode(' ', $parts), $bindings);
    }

    protected function compileCte(SelectQuery $query): ?Expression
    {
        $cte = $query->getCte();
        if (empty($cte)) {
            return null;
        }
        $parts = [];
        $bindings = [];
        foreach ($cte as $alias => $expr) {
            $parts[] = "{$this->wrap($alias)} as ({$expr->toSql()})";
            $bindings = array_merge($bindings, $expr->getBindings());
        }
        return new Expression(
            'with ' . implode(', ', $parts),
            $bindings,
        );


    }

    protected function compileSelect(SelectQuery $query): Expression
    {
        $columns = [];
        $bindings = [];
        foreach ($query->getColumns() as $i => $column) {
            if ($column instanceof Expression) {
                $columns[] = $column->sql;
                $bindings = [...$bindings, $column->bindings];
                continue;
            }
            if (is_string($i)) {
                $colName = "$i as $column";
            } else {
                $colName = $column;
            }
            $columns[] = $this->wrap($colName);
        }
        $parts = array_filter([
            'select',
            $query->isDistinct() ? 'distinct' : '',
            implode(', ', $columns),
            'from',
            $this->compileFrom($query->getFrom())
        ]);
        return new Expression(implode(' ', $parts));
    }

    protected function compileFrom(FromClause $from): string
    {
        $table = $this->wrap($from->getTable());
        if (!empty($from->getAlias())) {
            $table .= (' as ' . $this->wrap($from->getAlias()));
        }
        return $table;
    }

    protected function compileWhere(SelectQuery $query): ?Expression
    {
        if (empty($query->getWhere())) {
            return null;
        }
        $compiled = $this->conditionsCompiler->compile($query->getWhere()->getConditions());
        return new Expression('where ' . $compiled->sql, $compiled->bindings);
    }

    protected function compileJoins(SelectQuery $query): Expression
    {
        $compiled = [];
        foreach ($query->getJoins() as $join) {
            $compiledConditions = $this->conditionsCompiler->compile($join->getConditions());
            $compiled[] = new Expression(
                sprintf('%s join %s on %s', $join->getType()->value, $this->compileFrom($join->getFrom()), $compiledConditions->sql),
                $compiledConditions->bindings,
            );
        }
        return $this->mergeParts(...$compiled);
    }

    protected function compileHaving(SelectQuery $query): ?Expression
    {
        if (empty($query->getHaving())) {
            return null;
        }
        $compiled = $this->conditionsCompiler->compile($query->getHaving()->getConditions());

        return new Expression('having ' . $compiled->sql, $compiled->bindings);
    }


    protected function compileLimit(SelectQuery $query, bool $union = false): ?Expression
    {
        $limit = $union ? $query->getUnionLimit() : $query->getLimit();
        if ($limit === null) {
            return null;
        }
        return new Expression('limit ' . $limit);
    }

    protected function compileOffset(SelectQuery $query, bool $union = false): ?Expression
    {
        $offset = $union ? $query->getUnionOffset() : $query->getOffset();
        if ($offset === null || $offset <= 0) {
            return null;
        }
        return new Expression('offset ' . $offset);
    }

    protected function compileOrderBy(SelectQuery $query, bool $union = false): ?Expression
    {
        $orderBy = $union ? $query->getUnionOrderBy() : $query->getOrderBy();
        if (empty($orderBy)) {
            return null;
        }
        $parts = [];
        $bindings = [];
        foreach ($orderBy as $column => $direction) {
            if ($direction instanceof Expression) {
                $parts[] = $direction->sql;
                $bindings = [...$bindings, ...$direction->bindings];
                continue;
            }
            $column = $this->wrap($column);
            $parts[] = "{$column} {$direction}";
        }
        return new Expression('order by ' . implode(', ', $parts), $bindings);
    }

    protected function compileGroupBy(SelectQuery $query): ?Expression
    {
        if (empty($query->getGroupBy())) {
            return null;
        }
        $parts = [];
        $bindings = [];
        foreach ($query->getGroupBy() as $column => $value) {
            if ($value instanceof Expression) {
                $parts[] = $value->sql;
                $bindings = [...$bindings, ...$value->bindings];
                continue;
            }
            $parts[] = $this->wrap($column);

        }
        return new Expression('group by ' . implode(', ', $parts), $bindings);
    }

    protected function mergeParts(Expression ...$parts): Expression
    {
        $sqlParts = [];
        $bindings = [];
        foreach ($parts as $part) {
            if (empty($part->sql)) {
                continue;
            }
            $sqlParts[] = $part->sql;
            $bindings[] = $part->bindings;
        }
        return new Expression(implode(' ', $sqlParts), array_merge(...$bindings));
    }
}