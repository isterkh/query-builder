<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Traits;

use Isterkh\QueryBuilder\Components\Condition;
use Isterkh\QueryBuilder\Components\ConditionGroup;
use Isterkh\QueryBuilder\Components\Expression;

trait HasConditionTrait
{
    use RawExpressionTrait;

    protected function add(
        \Closure|Expression|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
        bool $isOr = false,
        bool $rightIsColumn = false
    ): static {
        if ($column instanceof Expression) {
            $this->addCondition($column, $isOr);

            return $this;
        }
        if ($column instanceof \Closure) {
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

    protected function addCondition(Condition|ConditionGroup|Expression $condition, bool $isOr = false): static
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
        if (null !== $last) {
            $this->getConditions()->pop();
            $orGroup->add($last);
        }
        $orGroup->add($condition);
        $this->getConditions()->add($orGroup);

        return $this;
    }

    /**
     * @return array<mixed, mixed>
     */
    protected function parseOperatorValue(
        mixed $operatorOrValue = null,
        mixed $value = null
    ): array {
        if (null === $value && !in_array($operatorOrValue, ['=', '!='], true)) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }

        return [$operator, $value];
    }

    protected function squashCondition(Condition|ConditionGroup|Expression $condition): Condition|ConditionGroup|Expression
    {
        return $condition instanceof ConditionGroup && 1 === count($condition->getConditions())
            ? $condition->getConditions()[array_key_first($condition->getConditions())]
            : $condition;
    }
}
