<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Expressions;

class ExpressionBuilder
{
    /**
     * @var string[]
     */
    protected array $parts = [];

    /**
     * @var mixed[]
     */
    protected array $bindings = [];

    public function __construct(
        protected ?string $prefix = null,
        protected ?string $suffix = null,
        protected string $separator = ', '
    ) {}

    /**
     * @param mixed[] $bindings
     */
    public function add(
        string $sql,
        array $bindings = []
    ): static {
        if (empty($sql)) {
            return $this;
        }
        $this->parts[] = $sql;
        $this->bindings = [...$this->bindings, ...$bindings];

        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function separator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    public function get(): Expression
    {
        $sql = implode($this->separator, $this->parts);
        if (empty($sql)) {
            return new Expression('');
        }

        return new Expression($this->prefix . $sql . $this->suffix, $this->bindings);
    }
}
