<?php

declare(strict_types=1);

namespace Tests\MySql\SelectQuery;

/**
 * @internal
 *
 * @coversNothing
 */
class GroupByTest extends SelectQueryTestTemplate
{
    public function testGroupBy(): void
    {
        $q = $this->query
            ->groupBy('a', 'b', 'c')
            ->groupBy('a', 'b')
            ->groupBy('c', 'd')
        ;
        static::assertStringEndsWith(
            'group by `a`, `b`, `c`, `d`',
            $q->toSql()
        );
    }

    public function testGroupByRaw(): void
    {
        $q = $this->query
            ->groupByRaw('   year(created_at), month(created_at), day(created_at)   ')
            ->groupBy('user_id')
        ;
        static::assertStringEndsWith(
            'group by year(created_at), month(created_at), day(created_at), `user_id`',
            $q->toSql()
        );
    }

    public function testGroupByEmpty(): void
    {
        $q = $this->query
            ->groupBy()
            ->groupByRaw('   ')
        ;
        static::assertSame(
            'select * from `t`',
            $q->toSql()
        );
    }
}
