<?php
declare(strict_types=1);


namespace Tests\MySql\SelectQuery;

use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\Queries\SelectQuery;

class UnionTest extends SelectQueryTestTemplate
{
    public function testUnion()
    {
        $q = $this->query
            ->where('a', 10)
            ->union(fn(SelectQuery $q) => $q->select('*')
                ->from('t2')
                ->where('b', 20)
            );
        $this->assertSame(
            '(select * from `t` where `a` = ?) union (select * from `t2` where `b` = ?)',
            $q->toSql()
        );
        $this->assertSame([10, 20], $q->getBindings());
    }

    public function testUnionAll()
    {
        $q = $this->query
            ->where('a', 10)
            ->unionAll(fn(SelectQuery $q) => $q->select('*')
                ->from('t2')
                ->where('b', 20)
            );
        $this->assertSame(
            '(select * from `t` where `a` = ?) union all (select * from `t2` where `b` = ?)',
            $q->toSql()
        );
        $this->assertSame([10, 20], $q->getBindings());
    }

    public function testNestedUnion(): void
    {
        $q = $this->query
            ->where('a', 10)
            ->union(fn(SelectQuery $q) => $q->select('*')
                ->from('t2')
                ->where('b', 20)
                ->unionAll(fn(SelectQuery $q) => $q->select('*')
                    ->from('t3')
                    ->where('c', 30)
                )
            );
        $this->assertSame(
            '(select * from `t` where `a` = ?) union ((select * from `t2` where `b` = ?) union all (select * from `t3` where `c` = ?))',
            $q->toSql()
        );
        $this->assertSame([10, 20, 30], $q->getBindings());
    }

    public function testEmptyUnion(): void
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage('Missing from clause');

        $this->query
            ->where('a', 10)
            ->union(fn(SelectQuery $q) => $q)
            ->toSql();
    }

    public function testUnionWithOrderLimitOffset(): void
    {
        $q = $this->query
            ->orderBy('a')
            ->limit(10)
            ->offset(10)
            ->union(static fn(SelectQuery $q) => $q
                ->select('*')
                ->from('t2')
                ->orderBy('a', 'desc')
                ->limit(5)
                ->offset(5)
            )
            ->orderBy('b', 'desc')
            ->limit(5)
            ->offset(50);

        $this->assertSame(
            '(select * from `t` order by `a` asc limit 10 offset 10) union (select * from `t2` order by `a` desc limit 5 offset 5) order by `b` desc limit 5 offset 50',
            $q->toSql()
        );
    }
}