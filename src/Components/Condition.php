<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Components;

class Condition
{
    public function __construct(
        protected string $column,
        protected string $operator,
        protected mixed $value,
        protected bool $rightIsColumn = false
    ) {
        $this->operator = trim(strtolower($this->operator));
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

    public function isRightIsColumn(): bool
    {
        return $this->rightIsColumn;
    }
}
