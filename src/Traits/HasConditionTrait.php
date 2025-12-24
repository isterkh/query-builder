<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Traits;

use Closure;
use Isterkh\QueryBuilder\Condition\Condition;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Expressions\Expression;

trait HasConditionTrait
{
    use RawExpressionTrait;
    protected function add(
        string|Expression|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
        bool $isOr = false,
        bool $rightIsColumn = false
    ): static
    {
        if ($column instanceof Expression) {
            $this->addCondition($column, $isOr);
            return $this;
        }
        if ($column instanceof Closure) {
            /**
             * @phpstan-ignore-next-line
             */
            $subClause = method_exists($this, 'newInstance') ? $this->newInstance() : new static(new ConditionGroup());
            $column($subClause);
            $this->addCondition($subClause->getConditions(), $isOr);
            return $this;
        }

        [$operator, $value] = $this->parseOperatorValue($operatorOrValue, $value);
        $condition = new Condition($column, $operator, $value, $rightIsColumn);
        return $this->addCondition($condition, $isOr);
    }
    protected function addCondition(Condition|Expression|ConditionGroup $condition, bool $isOr = false): static
    {

        $condition = $this->squashCondition($condition);
        if (!$isOr) {
            $this->getConditions()->add($condition);
            return $this;
        }
        $last = $this->getConditions()->getLast();
        if ($last instanceof ConditionGroup && $last->isOr()) {
            $last->add($condition);
            return $this;
        }
        $orGroup = new ConditionGroup(true);
        if ($last !== null) {
            $this->getConditions()->pop();
            $orGroup->add($last);
        }
        $orGroup->add($condition);
        $this->getConditions()->add($orGroup);
        return $this;

    }

    /**
     * @param mixed|null $operatorOrValue
     * @param mixed|null $value
     * @return array<mixed, mixed>
     */
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

    protected function squashCondition(Condition|Expression|ConditionGroup $condition): ConditionGroup|Condition|Expression
    {
        return $condition instanceof ConditionGroup && count($condition->getConditions()) === 1
            ? $condition->getConditions()[array_key_first($condition->getConditions())]
            : $condition;
    }

}