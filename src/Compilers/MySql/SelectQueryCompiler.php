<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compilers\MySql;

use Isterkh\QueryBuilder\Clauses\FromClause;
use Isterkh\QueryBuilder\Compilers\DTO\CompiledQuery;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Exceptions\CompilerDoesNotSupportsQuery;
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
     * @return CompiledQuery
     */
    public function compile(QueryInterface $query): CompiledQuery
    {

        if (!$this->supports($query)) {
            throw new CompilerDoesNotSupportsQuery();
        }
        return $this->mergeParts(
            ...array_filter([
                $this->compileSelect($query),
                $this->compileJoins($query),
                $this->compileWhere($query),
                $this->compileGroupBy($query),
                $this->compileHaving($query),
                $this->compileOrderBy($query),
                $this->compileLimit($query),
                $this->compileOffset($query),
            ])
        );
    }

    protected function compileSelect(SelectQuery $query): CompiledQuery
    {
        $columns = [];
        foreach ($query->getColumns() as $i => $column) {

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
        return new CompiledQuery(implode(' ', $parts));
    }

    protected function compileFrom(FromClause $from): string
    {
        $table = $this->wrap($from->getTable());
        if (!empty($from->getAlias())) {
            $table .= (' as ' . $this->wrap($from->getAlias()));
        }
        return $table;
    }

    protected function compileWhere(SelectQuery $query): ?CompiledQuery
    {
        if (empty($query->getWhere())) {
            return null;
        }
        $compiled = $this->conditionsCompiler->compile($query->getWhere()->getConditions());
        return new CompiledQuery('where ' . $compiled->sql, $compiled->bindings);
    }

    protected function compileJoins(SelectQuery $query): CompiledQuery
    {
        $compiled = [];
        foreach ($query->getJoins() as $join) {
            $compiledConditions = $this->conditionsCompiler->compile($join->getConditions());
            $compiled[] = new CompiledQuery(
                sprintf('%s join %s on %s', $join->getType()->value, $this->compileFrom($join->getFrom()), $compiledConditions->sql),
                $compiledConditions->bindings,
            );
        }
        return $this->mergeParts(...$compiled);
    }

    protected function compileHaving(SelectQuery $query): ?CompiledQuery
    {
        if (empty($query->getHaving())) {
            return null;
        }
        $compiled = $this->conditionsCompiler->compile($query->getHaving()->getConditions());

        return new CompiledQuery('having ' . $compiled->sql, $compiled->bindings);
    }


    protected function compileLimit(SelectQuery $query): ?CompiledQuery
    {
        if ($query->getLimit() === null) {
            return null;
        }
        return new CompiledQuery('limit ' . $query->getLimit());
    }

    protected function compileOffset(SelectQuery $query): ?CompiledQuery
    {
        $offset = $query->getOffset();
        if ($offset === null || $offset <= 0) {
            return null;
        }
        return new CompiledQuery('offset ' . $query->getOffset());

    }

    protected function compileOrderBy(SelectQuery $query): ?CompiledQuery
    {
        if (empty($query->getOrderBy())) {
            return null;
        }
        $parts = [];
        foreach ($query->getOrderBy() as $column => $direction) {
            $column = $this->wrap($column);
            $parts[] = "{$column} {$direction}";
        }
        return new CompiledQuery('order by ' . implode(', ', $parts));
    }

    protected function compileGroupBy(SelectQuery $query): ?CompiledQuery
    {
        if (empty($query->getGroupBy())) {
            return null;
        }

        $groupBy = array_map(fn ($col) => $this->wrap($col), array_unique($query->getGroupBy()));

        return new CompiledQuery('group by ' . implode(', ', $groupBy));
    }

    protected function mergeParts(CompiledQuery ...$parts): CompiledQuery
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
        return new CompiledQuery(implode(' ', $sqlParts), array_merge(...$bindings));
    }
}