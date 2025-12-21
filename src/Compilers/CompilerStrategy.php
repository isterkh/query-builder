<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compilers;

use Closure;
use Isterkh\QueryBuilder\Compilers\DTO\CompiledQuery;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Exceptions\CompilerNotFoundException;
use RuntimeException;

class CompilerStrategy implements CompilerInterface
{
    protected array $factories = [];
    protected array $instances = [];

    protected function getCompiler(int $index): CompilerInterface
    {
        if (empty($this->instances[$index])) {
            $compiler = $this->factories[$index]();
            if (!($compiler instanceof CompilerInterface)) {
                throw new RuntimeException('Compiler not found');
            }
            $this->instances[$index] = $compiler;

        }
        return $this->instances[$index];
    }

    public function with(Closure $factory): static
    {
        $this->factories[] = $factory;
        return $this;
    }

    public function compile(QueryInterface $query): CompiledQuery
    {
        foreach ($this->factories as $i => $factory) {
            $compiler = $this->getCompiler($i);
            if ($compiler->supports($query)) {
                return $compiler->compile($query);
            }
        }
        throw new CompilerNotFoundException('Compiler not found for query: ' . get_class($query));
    }

    public function supports(QueryInterface $query): bool
    {
        foreach ($this->factories as $i => $factory) {
            $compiler = $this->getCompiler($i);
            if ($compiler->supports($query)) {
                return true;
            }
        }

        return false;
    }
}