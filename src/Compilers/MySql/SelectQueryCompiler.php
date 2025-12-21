<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compilers\MySql;

use Isterkh\QueryBuilder\Compilers\DTO\CompiledQuery;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Exceptions\CompilerDoesNotSupportsQuery;
use Isterkh\QueryBuilder\Queries\SelectQuery;


class SelectQueryCompiler implements CompilerInterface
{
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
        $table = $query->getFrom()->getTable();
        $tableAlias = $query->getFrom()->getAlias();
        $columns = [];
        foreach ($query->getColumns() as $i => $column) {
            if (is_string($i)) {
                $columns[] = "$i AS $column";
            } else {
                $columns[] = $column;
            }
        }
        return new CompiledQuery(sprintf('SELECT %s FROM %s%s',
            implode(', ', $columns),
            $query->getFrom()->getTable(),
            $tableAlias ? " $tableAlias" : ''
        ));
    }


    protected function compileWhere(SelectQuery $query): ?CompiledQuery
    {
        if (empty($query->getWhere())) {
            return null;
        }
        $compiled = $this->compileConditions($query->getWhere()->getConditions());
        return new CompiledQuery('WHERE ' . $compiled->sql, $compiled->bindings);
    }

    protected function compileHaving(SelectQuery $query): ?CompiledQuery
    {
        if (empty($query->getHaving())) {
            return null;
        }
        $compiled = $this->compileConditions($query->getHaving()->getConditions());

        return new CompiledQuery('HAVING ' . $compiled->sql, $compiled->bindings);
    }


    protected function compileLimit(SelectQuery $query): ?CompiledQuery
    {
        if ($query->getLimit() <= 0) {
            return null;
        }
        return new CompiledQuery('LIMIT ' . $query->getLimit());
    }

    protected function compileOffset(SelectQuery $query): ?CompiledQuery
    {
        if ($query->getOffset() <= 0) {
            return null;
        }
        return new CompiledQuery('OFFSET ' . $query->getOffset());

    }

    protected function compileOrderBy(SelectQuery $query): ?CompiledQuery
    {
        if (empty($query->getOrderBy())) {
            return null;
        }
        $parts = [];
        foreach ($query->getOrderBy() as $column => $direction) {
            $parts[] = "{$column} {$direction}";
        }
        return new CompiledQuery('ORDER BY ' . implode(', ', $parts));
    }

    protected function compileGroupBy(SelectQuery $query): ?CompiledQuery
    {
        if (empty($query->getGroupBy())) {
            return null;
        }

        $groupBy = array_unique($query->getGroupBy());

        return new CompiledQuery('GROUP BY ' . implode(', ', $groupBy));
    }


    protected function compileConditions(ConditionGroup $conditionGroup): CompiledQuery
    {
        $parts = [];
        $bindings = [];

        foreach ($conditionGroup->getConditions() as $condition) {
            if ($condition instanceof ConditionGroup) {
                $compiled = $this->compileConditions($condition);
                $parts[] = "($compiled->sql)";
                $bindings = array_merge($bindings, $compiled->bindings ?? []);
            } else {
                if ($condition->getValue() === null && in_array($condition->getOperator(), ['=', '!='], true)) {
                    $operator = 'IS' . ($condition->getOperator() === '!=' ? ' NOT' : '');
                    $parts[] = "{$condition->getColumn()} $operator NULL";
                } elseif (in_array($condition->getOperator(), ['IN', 'NOT IN'], true) && is_array($condition->getValue())) {
                    $placeholders = implode(', ', array_fill(0, count($condition->getValue()), '?'));
                    $parts[] = "{$condition->getColumn()} {$condition->getOperator()} ($placeholders)";
                    $bindings = array_merge($bindings, $condition->getValue());
                } elseif ($condition->getOperator() === 'BETWEEN' || $condition->getOperator() === 'NOT BETWEEN') {
                    $parts[] = "{$condition->getColumn()} {$condition->getOperator()} ? AND ?";
                    $bindings = array_merge($bindings, $condition->getValue());

                } else {
                    $parts[] = "{$condition->getColumn()} {$condition->getOperator()} ?";
                    $bindings[] = $condition->getValue();
                }
            }
        }
        $separator = $conditionGroup->isOr() ? ' OR ' : ' AND ';

        return new CompiledQuery(implode($separator, $parts), $bindings);
    }

    protected function mergeParts(CompiledQuery ...$parts): CompiledQuery
    {
        $sqlParts = [];
        $bindings = [];
        foreach ($parts as $part) {
            $sqlParts[] = $part->sql;
            $bindings[] = $part->bindings;
        }
        return new CompiledQuery(implode(' ', $sqlParts), array_merge(...$bindings));
    }
}