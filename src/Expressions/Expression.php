<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Expressions;

use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;

class Expression
{
    /**
     * @param string $sql
     * @param array<mixed> $bindings
     */
    public function __construct(
        protected  string $sql,
        protected array  $bindings = []
    )
    {
        $this->sql = trim($this->sql);
    }

    public function merge(Expression ...$expressions): static
    {
        $sql = [$this->sql];
        $bindings = array_values($this->bindings);
        foreach ($expressions as $expression) {
            $sql[] = $expression->getSql();
            $bindings = [...$bindings, ...array_values($expression->getBindings())];
        }
        /**
         * @phpstan-ignore-next-line
         */
        return new static(implode(' ', array_filter($sql)), $bindings);
    }

    /**
     * @param Expression ...$expressions
     * @return Expression
     */
    public static function fromExpressions(Expression ...$expressions): Expression
    {
        if (empty($expressions)) {
            throw new QueryBuilderException('Empty list of expressions');
        }
        $first = array_shift($expressions);
        return array_reduce(
            $expressions,
            static fn(Expression $carry, Expression $item) => $carry->merge($item),
            $first
        );
    }

    public function wrap(): static
    {
        /**
         * @phpstan-ignore-next-line
         */
        return new static("({$this->sql})", $this->bindings);
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return mixed[]
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}