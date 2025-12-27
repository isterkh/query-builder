<?php

declare(strict_types=1);

namespace Tests\Select;

use Isterkh\QueryBuilder\Clauses\JoinClause;
use Isterkh\QueryBuilder\Exceptions\CompilerException;

use Isterkh\QueryBuilder\QB;
use Isterkh\QueryBuilder\QueryBuilder;

/**
 * @internal
 *
 * @coversNothing
 */
class SelectComplexQueryTest extends SelectQueryTestTemplate
{
    // union
    public function testUnion(): void
    {
        $q = $this->query
            ->where('a', 10)
            ->union(
                fn (QB $q) => $q->select('*')
                    ->from('t2')
                    ->where('b', 20)
            )
        ;
        static::assertSame(
            '(select * from `t` where `a` = ?) union (select * from `t2` where `b` = ?)',
            $q->toSql()
        );
        static::assertSame([10, 20], $q->getBindings());
    }

    public function testUnionAll(): void
    {
        $q = $this->query
            ->where('a', 10)
            ->unionAll(
                fn (QB $q) => $q->select('*')
                    ->from('t2')
                    ->where('b', 20)
            )
        ;
        static::assertSame(
            '(select * from `t` where `a` = ?) union all (select * from `t2` where `b` = ?)',
            $q->toSql()
        );
        static::assertSame([10, 20], $q->getBindings());
    }

    public function testNestedUnion(): void
    {
        $q = $this->query
            ->where('a', 10)
            ->union(
                fn (QB $q) => $q->select('*')
                    ->from('t2')
                    ->where('b', 20)
                    ->unionAll(
                        fn (QB $q) => $q->select('*')
                            ->from('t3')
                            ->where('c', 30)
                    )
            )
        ;
        static::assertSame(
            '(select * from `t` where `a` = ?) union ((select * from `t2` where `b` = ?) union all (select * from `t3` where `c` = ?))',
            $q->toSql()
        );
        static::assertSame([10, 20, 30], $q->getBindings());
    }

    public function testEmptyUnion(): void
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage('Missing from clause');

        $this->query
            ->where('a', 10)
            ->union(fn (QB $q) => $q)
            ->toSql()
        ;
    }

    public function testUnionWithOrderLimitOffset(): void
    {
        $q = $this->query
            ->orderBy('a')
            ->limit(10)
            ->offset(10)
            ->union(
                static fn (QB $q) => $q
                    ->select('*')
                    ->from('t2')
                    ->orderBy('a', 'desc')
                    ->limit(5)
                    ->offset(5)
            )
            ->orderBy('b', 'desc')
            ->limit(5)
            ->offset(50)
        ;

        static::assertSame(
            '(select * from `t` order by `a` asc limit 10 offset 10) union (select * from `t2` order by `a` desc limit 5 offset 5) order by `b` desc limit 5 offset 50',
            $q->toSql()
        );
    }

    public function testCte(): void
    {
        $q = $this->builder
            ->with(
                'salary_rank',
                fn (QB $sub) => $sub
                    ->selectRaw('id, dense_rank() over (partition by id order by salary desc) as rnk')
                    ->from('salaries')
            )
            ->with(
                'top_bonuses',
                fn (QB $sub) => $sub
                    ->selectRaw('id, max(bonus) as bonus')
                    ->groupBy('id')
                    ->from('bonuses')
            )
            ->select('id', 'salary', 'bonus', 'rnk as rank')
            ->from('salary_rank', 'sr')
            ->join(
                'top_bonuses',
                static fn (JoinClause $join) => $join
                    ->on('sr.id', 'tb.id')
                    ->on('tb.bonus', '>=', 'sr.salary'),
                'tb'
            )
            ->where('sr.rnk', '<=', 3)
        ;
        static::assertSame(
            'with `salary_rank` as (select id, dense_rank() over (partition by id order by salary desc) as rnk from `salaries`), `top_bonuses` as (select id, max(bonus) as bonus from `bonuses` group by `id`) select `id`, `salary`, `bonus`, `rnk` as `rank` from `salary_rank` as `sr` inner join `top_bonuses` as `tb` on `sr`.`id` = `tb`.`id` and `tb`.`bonus` >= `sr`.`salary` where `sr`.`rnk` <= ?',
            $q->toSql()
        );
        static::assertSame([3], $q->getBindings());
    }

    public function testEdgeCases(): void
    {
        $query = $this->builder
            ->select(['id', 'name'])
            ->from('users')
            ->whereRaw('age > ?', [18])
            ->whereRaw('')
            ->where('status', 'active')
            ->join('profiles', fn (JoinClause $join) => $join)
            ->groupBy('country', 'city')
            ->orderBy('name')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->offset(5)
            ->union(fn (QB $q) => $q->select('id', 'name')->from('admin')->where('role', 'super'))
            ->unionAll(fn (QB $q) => $q->from('guests'))
        ;

        $sql = $query->toSql();

        static::assertStringContainsString('select `id`, `name` from `users`', $sql);
        static::assertStringContainsString('where age > ? and `status` = ?', $sql);
        static::assertStringContainsString('group by `country`, `city`', $sql);
        static::assertStringContainsString('order by `name` asc, `id` desc', $sql);
        static::assertStringContainsString('limit 10 offset 5', $sql);
        static::assertStringContainsString('union', $sql);

        static::assertSame([18, 'active', 'super'], $query->getBindings());
    }
}
