<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compilers\MySql;

use Isterkh\QueryBuilder\Compilers\DTO\CompiledQuery;
use Isterkh\QueryBuilder\Condition\Condition;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\Exceptions\UnsupportedOperatorException;
use Isterkh\QueryBuilder\Traits\WrapColumnsTrait;

class ConditionsCompiler
{
    use WrapColumnsTrait;
    protected array $operators = [
        '=', '!=', '<>', '<', '>', '<=', '>=',
        'in', 'not in',
        'between', 'not between',
        'like', 'not like',
        'is', 'is not'
    ];

    protected array $specialHandlers = [
        'in' => 'compileIn',
        'not in' => 'compileIn',
        'between' => 'compileBetween',
        'not between' => 'compileBetween',
    ];
    protected array $exactEqualityOperators = [
        '=', '!=', '<>'
    ];

    public function compile(ConditionGroup $conditionGroup): CompiledQuery
    {
        $parts = [];
        $bindings = [];

        foreach ($conditionGroup->getConditions() as $condition) {
            if ($condition instanceof ConditionGroup) {
                $compiled = $this->compile($condition);
                $parts[] = "($compiled->sql)";
                $bindings = array_merge($bindings, $compiled->bindings ?? []);
            } else {
                $compiled = $this->compileSingleCondition($condition);
                $parts[] = "$compiled->sql";
                $bindings = array_merge($bindings, $compiled->bindings ?? []);
            }
        }
        $separator = $conditionGroup->isOr() ? ' or ' : ' and ';

        return new CompiledQuery(implode($separator, $parts), $bindings);
    }

    protected function compileSingleCondition(Condition $condition): CompiledQuery
    {
        $operator = $condition->getOperator();
        $this->ensureOperatorIsSupported($operator);

        $value = $condition->getValue();

        $preparedCondition = new Condition(
            $this->wrap($condition->getColumn()),
            $condition->getOperator(),
            $condition->isRightIsColumn() ? $this->wrap($condition->getValue()) : $condition->getValue(),
            $condition->isRightIsColumn()
        );

        if ($value === null && in_array($operator, $this->exactEqualityOperators, true)) {
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

    protected function compileNull(Condition $condition): CompiledQuery
    {
        $operator = $condition->getOperator() === '=' ? 'is' : 'is not';
        return new CompiledQuery("{$condition->getColumn()} {$operator} null");
    }

    protected function compileBetween(Condition $condition): CompiledQuery
    {
        $value = $condition->getValue();
        if (count($value) !== 2) {
            throw new CompilerException("There must be exactly two values for between condition");
        }
        if ($condition->isRightIsColumn()) {
            return new CompiledQuery(
                "{$condition->getColumn()} {$condition->getOperator()} {$value[0]} and {$value[1]}"
            );
        }

        return new CompiledQuery(
            "{$condition->getColumn()} {$condition->getOperator()} ? and ?",
            $value
        );

    }

    protected function compileIn(Condition $condition): CompiledQuery
    {
        $value = $condition->getValue();
        if (empty($value)) {
            return new CompiledQuery('');
        }
        $placeholders = implode(', ', array_fill(0, count($value), '?'));
        return new CompiledQuery(
            "{$condition->getColumn()} {$condition->getOperator()} ({$placeholders})",
            $value
        );
    }

    protected function compileDefault(Condition $condition): CompiledQuery
    {
        if ($condition->isRightIsColumn()) {
            return new CompiledQuery(
                "{$condition->getColumn()} {$condition->getOperator()} {$condition->getValue()}"
            );
        }
        return new CompiledQuery(
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