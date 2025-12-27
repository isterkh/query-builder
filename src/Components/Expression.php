<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Components;

use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;

class Expression
{
    /**
     * @param array<mixed> $bindings
     */
    public function __construct(
        protected string $sql,
        protected array $bindings = []
    ) {
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

        // @phpstan-ignore-next-line
        return new static(implode(' ', array_filter($sql)), $bindings);
    }

    public static function fromExpressions(Expression ...$expressions): Expression
    {
        if (empty($expressions)) {
            throw new QueryBuilderException('Empty list of expressions');
        }
        $first = array_shift($expressions);

        return array_reduce(
            $expressions,
            static fn (Expression $carry, Expression $item) => $carry->merge($item),
            $first
        );
    }

    public function wrap(string $before = '(', string $after = ')'): static
    {
        return $this->modifySql(static fn (string $sql) => $before . $sql . $after);
    }

    public function prefix(string $prefix): static
    {
        return $this->modifySql(static fn (string $sql) => $prefix . $sql);
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

    public function isEmpty(): bool
    {
        return empty($this->sql);
    }

    /**
     * @return array<int, mixed[]|string>
     */
    public function toArray(): array
    {
        return [$this->sql, $this->bindings];
    }

    protected function modifySql(\Closure $callback): static
    {
        if (empty($this->sql)) {
            return $this;
        }
        $new = clone $this;
        $new->sql = $callback($new->sql);

        return $new;
    }
}
