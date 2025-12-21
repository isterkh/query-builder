<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Condition;

class Condition
{
    public function __construct(
        protected string $column,
        protected string $operator,
        protected mixed $value
    )
    {
    }

    public function getColumn(): string
    {
        return $this->column;
    }
    public function getOperator(): string
    {
        return $this->operator;
    }
    public function getValue(): mixed
    {
        return $this->value;
    }
}