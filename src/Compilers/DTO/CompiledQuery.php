<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Compilers\DTO;

readonly class CompiledQuery
{
    public function __construct(
        public string $sql,
        public array  $bindings = []
    )
    {
    }
}