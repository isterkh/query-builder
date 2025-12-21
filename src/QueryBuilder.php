<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder;

use InvalidArgumentException;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Queries\SelectQuery;

class QueryBuilder
{
    public function __construct(
        protected CompilerInterface $compiler,
    )
    {
    }

        public function select(array|string ...$columns): SelectQuery
        {

            return new SelectQuery($this->compiler,$this->normalizeColumns($columns));
        }


        protected function normalizeColumns(array $columns): array
        {
            if (empty($columns)) {
                return ['*'];
            }
            if (count($columns) === 1 && is_array($columns[0])) {
                return $columns[0];
            }
            foreach ($columns as $key => $column) {
                if (!is_string($column) || !(is_int($key) || is_string($key))) {
                    throw new InvalidArgumentException('Wrong argument');
                }
            }
            return $columns;
        }
}