<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers\Traits;

use Isterkh\QueryBuilder\Components\Condition;
use Isterkh\QueryBuilder\Components\ConditionGroup;
use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Exceptions\CompilerException;

trait CompilesConditionsTrait
{
    /**
     * @var array|string[]
     */
    protected array $specialHandlers = [
        'in' => 'compileInCondition',
        'not in' => 'compileInCondition',
        'between' => 'compileBetweenCondition',
        'not between' => 'compileBetweenCondition',
    ];

    /**
     * @var array|string[]
     */
    protected array $exactEqualityOperators = [
        '=', '!=', '<>',
    ];

    // TODO: Сделать адекватнее проверку на пустые условия.
    protected function compileConditions(ConditionGroup $conditionGroup): Expression
    {
        return $this->makeExpression(
            source: $conditionGroup->getConditions(),
            separator: $conditionGroup->isOr() ? ' or ' : ' and ',
            formatted: fn (int $i, Condition|ConditionGroup|Expression $cond) => $this->conditionToArray($cond)
        );
    }

    /**
     * @return array<mixed[]|string>
     */
    protected function conditionToArray(
        Condition|ConditionGroup|Expression $condition,
    ): array {
        if ($condition instanceof Expression) {
            return $condition->toArray();
        }
        if ($condition instanceof ConditionGroup) {
            return $this->compileConditions($condition)->wrap()->toArray();
        }

        return $this->compileSingleCondition($condition)->toArray();
    }

    protected function compileSingleCondition(Condition|Expression $condition): Expression
    {
        if ($condition instanceof Expression) {
            return $condition;
        }
        $operator = $condition->getOperator();

        $value = $condition->getValue();

        $preparedCondition = new Condition(
            $this->wrap($condition->getColumn()),
            $condition->getOperator(),
            $condition->isRightIsColumn() ? $this->wrap($condition->getValue()) : $condition->getValue(),
            $condition->isRightIsColumn()
        );

        if ($value === null && in_array($operator, $this->exactEqualityOperators, true)) {
            return $this->compileNullCondition($preparedCondition);
        }

        $handler = $this->specialHandlers[$operator] ?? null;
        if ($handler) {
            return $this->{$handler}($preparedCondition);
        }

        return $this->compileDefaultCondition($preparedCondition);
    }

    protected function compileNullCondition(Condition $condition): Expression
    {
        $operator = $condition->getOperator() === '=' ? 'is' : 'is not';

        return new Expression("{$condition->getColumn()} {$operator} null");
    }

    protected function compileBetweenCondition(Condition $condition): Expression
    {
        $value = $condition->getValue();
        if (count($value) !== 2) {
            throw new CompilerException('There must be exactly two values for between condition');
        }
        if ($condition->isRightIsColumn()) {
            return new Expression(
                "{$condition->getColumn()} {$condition->getOperator()} {$value[0]} and {$value[1]}"
            );
        }

        return new Expression(
            "{$condition->getColumn()} {$condition->getOperator()} ? and ?",
            $value
        );
    }

    protected function compileInCondition(Condition $condition): Expression
    {
        $value = $condition->getValue();
        if (empty($value)) {
            return new Expression('');
        }
        $placeholders = implode(', ', array_fill(0, count($value), '?'));

        return new Expression(
            "{$condition->getColumn()} {$condition->getOperator()} ({$placeholders})",
            $value
        );
    }

    protected function compileDefaultCondition(Condition $condition): Expression
    {
        if ($condition->isRightIsColumn()) {
            return new Expression(
                "{$condition->getColumn()} {$condition->getOperator()} {$condition->getValue()}"
            );
        }

        return new Expression(
            "{$condition->getColumn()} {$condition->getOperator()} ?",
            [$condition->getValue()]
        );
    }
}
