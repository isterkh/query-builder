<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compilers;

use Isterkh\QueryBuilder\Clauses\WhereClause;
use Isterkh\QueryBuilder\Compilers\DTO\CompiledQuery;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Queries\Query;

class MySqlCompiler implements CompilerInterface
{
    public function __construct(
        protected Query $query
    )
    {
    }
    public function compile(QueryInterface $query): CompiledQuery
    {
        $sql = sprintf(
            'SELECT %s FROM %s',
            implode(', ', $this->query->getColumns()),
            $this->query->getFrom()->getTable()
        );
        $bindings = [];
        if (!empty($this->query->getWhere())) {
            [$where, $whereBindings] = $this->compileWhere($this->query->getWhere());
            $sql .= ' WHERE ' . $where;
            $bindings = array_merge($bindings, $whereBindings);
        }

        return [$sql, $bindings];

    }
    protected function compileWhere(WhereClause $where): array
    {
        return $this->compileConditions($where->getConditions());
    }
    protected function compileConditions(ConditionGroup $conditionGroup): array
    {
        $parts = [];
        $bindings = [];
        foreach ($conditionGroup->getConditions() as $condition) {
            if ($condition instanceof ConditionGroup) {
                [$sql, $nestedBindings] = $this->compileConditions($condition);
                $parts[] = "($sql)";
                $bindings = array_merge($bindings, $nestedBindings);
            } else {
                $parts[] = "{$condition->getColumn()} {$condition->getOperator()} ?";
                $bindings[] = $condition->getValue();
            }
        }
        $separator = $conditionGroup->isOr() ? ' OR ' : ' AND ';
        return [implode($separator, $parts), $bindings];
    }
}