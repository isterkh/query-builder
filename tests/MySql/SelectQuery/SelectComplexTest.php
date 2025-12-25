<?php

declare(strict_types=1);

namespace Tests\MySql\SelectQuery;

use Isterkh\QueryBuilder\Clauses\JoinClause;
use Isterkh\QueryBuilder\Queries\SelectQuery;
use Isterkh\QueryBuilder\QueryBuilder;
use Tests\MySql\QueryTestTemplate;

/**
 * @internal
 *
 * @coversNothing
 */
class SelectComplexTest extends QueryTestTemplate
{
    public function testCte(): void
    {
        $q = $this->builder
            ->with(
                'salary_rank',
                fn (QueryBuilder $sub) => $sub
                    ->selectRaw('id, dense_rank() over (partition by id order by salary desc) as rnk')
                    ->from('salaries')
            )
            ->with(
                'top_bonuses',
                fn (QueryBuilder $sub) => $sub
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
            ->union(fn (SelectQuery $q) => $q->select('id', 'name')->from('admin')->where('role', 'super'))
            ->unionAll(fn (SelectQuery $q) => $q->from('guests'))
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
