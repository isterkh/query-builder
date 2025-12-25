<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers\MySql;

use Isterkh\QueryBuilder\Compilers\MySql\Traits\BasicCompilerTrait;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Exceptions\CompilerDoesNotSupportsQuery;
use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\Queries\UpdateQuery;

class UpdateQueryCompiler implements CompilerInterface
{
    use BasicCompilerTrait;

    public function __construct(
        protected ConditionsCompiler $conditionsCompiler,
    ) {}

    /**
     * @param UpdateQuery $query
     */
    public function compile(QueryInterface $query): Expression
    {
        if (!$this->supports($query)) {
            throw new CompilerDoesNotSupportsQuery();
        }

        return $this->makeExpression([
            $this->compileUpdate($query),
            $this->compileValues($query),
            $this->compileWhere($query),
        ]);
    }

    public function supports(QueryInterface $query): bool
    {
        return $query instanceof UpdateQuery;
    }

    protected function compileUpdate(UpdateQuery $query): Expression
    {
        return new Expression($this->compileTable($query->getTable()))
            ->prefix('update')
        ;
    }

    protected function compileWhere(UpdateQuery $query): ?Expression
    {
        if (empty($query->getWhere())) {
            return null;
        }
        $compiled = $this->conditionsCompiler->compile($query->getWhere()->getConditions());
        if (empty($compiled->getSql())) {
            return null;
        }

        return new Expression($compiled->getSql(), $compiled->getBindings());
    }

    protected function compileValues(UpdateQuery $query): ?Expression
    {
        if (empty($query->getValues())) {
            throw new CompilerException('Empty update values.');
        }
        $parts = [];
        $bindings = [];
        foreach ($query->getValues() as $alias => $value) {
            if ($value instanceof Expression) {
                if (empty($value->getSql())) {
                    continue;
                }
                $parts[] = $value->getSql();
                $bindings = [...$bindings, ...$value->getBindings()];

                continue;
            }
            $parts[] = "{$this->wrap($alias)} = ?";
            $bindings[] = $value;
        }

        return new Expression(implode(', ', $parts), $bindings)->prefix('set');
    }
}
