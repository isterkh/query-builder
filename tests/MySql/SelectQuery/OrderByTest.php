<?php

declare(strict_types=1);

namespace Tests\MySql\SelectQuery;

/**
 * @internal
 *
 * @coversNothing
 */
class OrderByTest extends SelectQueryTestTemplate
{
    public function testBasicOrderBy(): void
    {
        $q = $this->query
            ->orderBy('a')
            ->orderBy('b', 'desc')
        ;
        static::assertStringEndsWith(
            'order by `a` asc, `b` desc',
            $q->toSql()
        );
    }

    public function testOrderByOverride(): void
    {
        $q = $this->query
            ->orderBy('a')
            ->orderBy('b', 'desc')
            ->orderBy('a', 'desc')
        ;
        static::assertStringEndsWith(
            'order by `a` desc, `b` desc',
            $q->toSql()
        );
    }

    public function testOrderByRaw(): void
    {
        $q = $this->query
            ->orderByRaw('  rand()   ')
        ;
        static::assertStringEndsWith(
            'order by rand()',
            $q->toSql()
        );
    }

    public function testOrderByEmpty(): void
    {
        $q = $this->query
            ->orderByRaw('   ', [1, 2, 3])
        ;
        static::assertSame(
            'select * from `t`',
            $q->toSql()
        );
    }
}
