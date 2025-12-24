<?php
declare(strict_types=1);


namespace Tests\MySql\SelectQuery;

use Isterkh\QueryBuilder\Queries\SelectQuery;

class OrderByTest extends SelectQueryTestTemplate
{
    public function testBasicOrderBy(): void
    {
        $q = $this->query
            ->orderBy('a')
            ->orderBy('b', 'desc');
        $this->assertStringEndsWith(
            'order by `a` asc, `b` desc',
            $q->toSql()
        );
    }
    public function testOrderByOverride(): void
    {
        $q = $this->query
            ->orderBy('a')
            ->orderBy('b', 'desc')
            ->orderBy('a', 'desc');
        $this->assertStringEndsWith(
            'order by `a` desc, `b` desc',
            $q->toSql()
        );
    }
    public function testOrderByRaw(): void
    {
        $q = $this->query
            ->orderByRaw('  rand()   ');
        $this->assertStringEndsWith(
            'order by rand()',
            $q->toSql()
        );
    }

    public function testOrderByEmpty(): void
    {
        $q = $this->query
            ->orderByRaw('   ', [1,2,3]);
        $this->assertSame(
            'select * from `t`',
            $q->toSql()
        );
    }


}