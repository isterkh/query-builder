<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Traits;

use Closure;
use Isterkh\QueryBuilder\Condition\Condition;
use Isterkh\QueryBuilder\Condition\ConditionGroup;

trait HasConditionTrait
{
    protected ConditionGroup $rootConditionGroup;

    protected function add(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
        bool $isOr = false
    ): static
    {
        if ($column instanceof Closure) {
            $subClause = new static();
            $column($subClause);
            $this->addCondition($subClause->getConditions(), $isOr);
            return $this;
        }

        [$operator, $value] = $this->parseOperatorValue($operatorOrValue, $value);
        $condition = new Condition($column, $operator, $value);
        return $this->addCondition($condition, $isOr);
    }
    protected function addCondition(Condition|ConditionGroup $condition, bool $isOr = false): static
    {

        $condition = $this->squashCondition($condition);
        if (!$isOr) {
            $this->rootConditionGroup->add($condition);
            return $this;
        }
        $last = $this->rootConditionGroup->getLast();
        if ($last instanceof ConditionGroup && $last->isOr()) {
            $last->add($condition);
            return $this;
        }
        $orGroup = new ConditionGroup(true);
        if ($last !== null) {
            $this->rootConditionGroup->pop();
            $orGroup->add($last);
        }
        $orGroup->add($condition);
        $this->rootConditionGroup->add($orGroup);
        return $this;

    }


    protected function parseOperatorValue(
        mixed  $operatorOrValue = null,
        mixed  $value = null
    ): array
    {
        if ($value === null && !in_array($operatorOrValue, ['=', '!='], true)) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }
        return [$operator, $value];
    }

    protected function squashCondition(Condition|ConditionGroup $condition): ConditionGroup|Condition
    {
        return $condition instanceof ConditionGroup && count($condition->getConditions()) === 1
            ? $condition->getConditions()[array_key_first($condition->getConditions())]
            : $condition;
    }

    public function getConditions(): ConditionGroup
    {
        return $this->rootConditionGroup;
    }

}