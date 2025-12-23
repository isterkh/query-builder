<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Expressions;

readonly class Expression
{
    public function __construct(
        public string $sql,
        public array  $bindings = []
    )
    {
    }

    public function merge(?Expression ...$expressions): static
    {

        $sql = [$this->sql];
        $bindings = $this->bindings;
        foreach ($expressions as $expression) {
            $sql[] = $expression->sql;
            $bindings = [...$bindings, ...$expression->bindings];
        }
        return new static(implode(' ', array_filter($sql)), $bindings);
    }

    /**
     * @param array<Expression> $expressions
     * @return static
     */
    public static function fromExpressions(Expression ...$expressions): static
    {
        $first = array_shift($expressions);
        return array_reduce(
            $expressions,
            static fn(Expression $carry, Expression $item) => $carry->merge($item),
            $first
        );
    }

    public function wrap(): Expression
    {
        return new static("({$this->sql})", $this->bindings);
    }
}