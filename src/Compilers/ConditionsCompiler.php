<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers;

use Isterkh\QueryBuilder\Compilers\Traits\MakeExpressionTrait;
use Isterkh\QueryBuilder\Compilers\Traits\WrapColumnsTrait;
use Isterkh\QueryBuilder\Components\Condition;
use Isterkh\QueryBuilder\Components\ConditionGroup;
use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\Exceptions\UnsupportedOperatorException;

class ConditionsCompiler
{
    use WrapColumnsTrait;
    use MakeExpressionTrait;

    /**
     * @var array|string[]
     */
    protected array $operators = [
        '=', '!=', '<>', '<', '>', '<=', '>=',
        'in', 'not in',
        'between', 'not between',
        'like', 'not like',
        'is', 'is not',
    ];

    /**
     * @var array|string[]
     */
    protected array $specialHandlers = [
        'in' => 'compileIn',
        'not in' => 'compileIn',
        'between' => 'compileBetween',
        'not between' => 'compileBetween',
    ];

    /**
     * @var array|string[]
     */
    protected array $exactEqualityOperators = [
        '=', '!=', '<>',
    ];

    // TODO: Сделать адекватнее проверку на пустые условия.
    public function compile(ConditionGroup $conditionGroup): Expression
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
            return $this->compile($condition)->wrap()->toArray();
        }

        return $this->compileSingleCondition($condition)->toArray();
    }

    protected function compileSingleCondition(Condition|Expression $condition): Expression
    {
        if ($condition instanceof Expression) {
            return $condition;
        }
        $operator = $condition->getOperator();
        $this->ensureOperatorIsSupported($operator);

        $value = $condition->getValue();

        $preparedCondition = new Condition(
            $this->wrap($condition->getColumn()),
            $condition->getOperator(),
            $condition->isRightIsColumn() ? $this->wrap($condition->getValue()) : $condition->getValue(),
            $condition->isRightIsColumn()
        );

        if (null === $value && in_array($operator, $this->exactEqualityOperators, true)) {
            return $this->compileNull($preparedCondition);
        }

        if (!empty($this->specialHandlers[$operator])) {
            $handler = $this->specialHandlers[$operator];
            if (!method_exists($this, $handler)) {
                throw new CompilerException('Cannot compile operator ' . $operator);
            }

            return $this->{$handler}($preparedCondition);
        }

        return $this->compileDefault($preparedCondition);
    }

    protected function compileNull(Condition $condition): Expression
    {
        $operator = '=' === $condition->getOperator() ? 'is' : 'is not';

        return new Expression("{$condition->getColumn()} {$operator} null");
    }

    protected function compileBetween(Condition $condition): Expression
    {
        $value = $condition->getValue();
        if (2 !== count($value)) {
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

    protected function compileIn(Condition $condition): Expression
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

    protected function compileDefault(Condition $condition): Expression
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

    protected function ensureOperatorIsSupported(string $operator): void
    {
        if (!in_array($operator, $this->operators, true)) {
            throw new UnsupportedOperatorException("Unsupported operator '{$operator}'");
        }
    }
}
