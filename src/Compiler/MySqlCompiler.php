<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compiler;

use Isterkh\QueryBuilder\Clauses\WhereClause;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Queries\SelectQuery;

class MySqlCompiler
{
    public function __construct(
        protected SelectQuery $query
    )
    {
    }
    public function compile(): array
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