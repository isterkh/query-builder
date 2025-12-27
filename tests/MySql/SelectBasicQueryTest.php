<?php

declare(strict_types=1);

namespace Tests\MySql;

use Isterkh\QueryBuilder\Components\HavingClause;
use Isterkh\QueryBuilder\Components\JoinClause;
use Isterkh\QueryBuilder\Components\WhereClause;
use Isterkh\QueryBuilder\Exceptions\CompilerException;
use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 *
 * @coversNothing
 */
class SelectBasicQueryTest extends SelectQueryTestTemplate
{
    #[DataProvider('selectColumnsProvider')]
    public function testSelectColumns(
        array|string $columns,
        string $expectedSql,
    ): void {
        $query = $this->builder->select($columns)->from('table');
        static::assertStringStartsWith(
            $expectedSql,
            $query->toSql()
        );
        static::assertEmpty($query->getBindings());
    }

    public static function selectColumnsProvider(): array
    {
        return [
            'empty string' => ['', 'select *'],
            'empty array' => [[], 'select *'],
            'asterisk' => ['*', 'select *'],
            'array-asterisk' => [['*'], 'select *'],
            'list' => [['a', 'b'], 'select `a`, `b`'],
            'list-alias' => [['a', 'b as c'], 'select `a`, `b` as `c`'],
            'list-array' => [['a', ['b', 'c']], 'select `a`, `b`, `c`'],
        ];
    }

    #[DataProvider('selectFromProvider')]
    public function testSelectFrom(
        string $table,
        string $expectedSql,
        ?string $alias = null
    ): void {
        $query = $this->builder->select()->from($table, $alias);
        static::assertSame(
            $expectedSql,
            $query->toSql()
        );
        static::assertEmpty($query->getBindings());
    }

    public static function selectFromProvider(): array
    {
        return [
            'table' => ['table', 'select * from `table`', null],
            'table-alias' => ['table as t', 'select * from `table` as `t`', null],
            'table-alias-param' => ['table', 'select * from `table` as `t`', 't'],
        ];
    }

    #[DataProvider('selectRawProvider')]
    public function testSelectRaw(
        string $sql,
        array $bindings = [],
    ): void {
        $query = $this->builder
            ->selectRaw($sql, $bindings)
            ->from('salaries')
        ;
        $result = 'select ' . $sql . ' from `salaries`';
        static::assertSame($result, $query->toSql());
        static::assertSame($bindings, $query->getBindings());
    }

    public static function selectRawProvider(): array
    {
        return [
            'without-bindings' => ['user_id, dense_rank() over (partition department_id order by salary desc) as salary_group', []],
            'with-bindings' => ['user_id, dense_rank() over (partition department_id order by salary desc) as salary_group, ? as ext_value', [15]],
        ];
    }

    public function testSelectDistinct(): void
    {
        $query = $this->builder
            ->select('a')
            ->distinct()
            ->distinct()
            ->from('table')
        ;

        static::assertSame(
            'select distinct `a` from `table`',
            $query->toSql()
        );
        static::assertSame([], $query->getBindings());
    }

    public function testEmptyFromSelect(): void
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage('Missing from clause');
        $this->builder
            ->select('a')
            ->toSql()
        ;
    }

    // JOINS
    public function testBasicJoins(): void
    {
        $query = $this->selectQuery
            ->join('t1', fn (JoinClause $join) => $join->on('t.id', 't1.t_id'))
            ->leftJoin('t2', fn (JoinClause $join) => $join->on('t.id', 't2.t_id'))
            ->rightJoin('t3', fn (JoinClause $join) => $join->on('t.id', 't3.t_id'))
        ;

        static::assertStringEndsWith(
            'inner join `t1` on `t`.`id` = `t1`.`t_id` left join `t2` on `t`.`id` = `t2`.`t_id` right join `t3` on `t`.`id` = `t3`.`t_id`',
            $query->toSql()
        );
        static::assertEquals([], $query->getBindings());
    }

    public function testConditionalJoins(): void
    {
        $query = $this->selectQuery
            ->join(
                't1',
                fn (JoinClause $join) => $join
                    ->on('t.name', '=', 't1.name')
                    ->orOn('t.email', 't1.email')
                    ->whereIn('t1.id', [1, 2, 3])
                    ->orWhereIn('t1.pid', [1, 2, 3])
            )
        ;
        static::assertStringEndsWith(
            'inner join `t1` on (`t`.`name` = `t1`.`name` or `t`.`email` = `t1`.`email`) and (`t1`.`id` in (?, ?, ?) or `t1`.`pid` in (?, ?, ?))',
            $query->toSql()
        );
        static::assertEquals([1, 2, 3, 1, 2, 3], $query->getBindings());
    }

    public function testEmptyJoins(): void
    {
        $query = $this->selectQuery
            ->join('t1', fn (JoinClause $join) => $join->where(fn (JoinClause $w) => $w))
            ->leftJoin('t2', fn (JoinClause $join) => $join)
            ->rightJoin('t3', fn (JoinClause $join) => $join)
        ;
        static::assertSame(
            'select * from `t` inner join `t1` left join `t2` right join `t3`',
            $query->toSql()
        );
    }

    // WHERE
    public function testWhereBasic(): void
    {
        $query = $this->selectQuery
            ->where('is_paid', 1)
            ->where('category', '!=', 'mobile')
            ->where('created_at', '>', '2025-02-12')
            ->orWhere('status', 'closed')
        ;
        static::assertStringEndsWith(
            'where `is_paid` = ? and `category` != ? and (`created_at` > ? or `status` = ?)',
            $query->toSql()
        );
        static::assertSame([1, 'mobile', '2025-02-12', 'closed'], $query->getBindings());
    }

    public function testWhereNested(): void
    {
        $query = $this->selectQuery
            ->where(
                static fn (WhereClause $w) => $w
                    ->where('status', 'in', [1, 2, 3, 4])
                    ->where(
                        static fn (WhereClause $w) => $w
                            ->where('force', 1)
                            ->orWhere('manual', true)
                    )
            )
            ->where('name', 'like', 'iphone%')
        ;
        static::assertStringEndsWith(
            'where (`status` in (?, ?, ?, ?) and (`force` = ? or `manual` = ?)) and `name` like ?',
            $query->toSql()
        );
        static::assertSame([1, 2, 3, 4, 1, true, 'iphone%'], $query->getBindings());
    }

    public function testWhereRaw(): void
    {
        $query = $this->selectQuery
            ->whereRaw('  status = ? and (force = ? or manual = ?) and YEAR(created_at) = ?  ', ['paid', 1, 1, 2025])
        ;
        static::assertStringEndsWith(
            'where status = ? and (force = ? or manual = ?) and YEAR(created_at) = ?',
            $query->toSql()
        );
        static::assertSame(['paid', 1, 1, 2025], $query->getBindings());
    }

    public function testWhereIn(): void
    {
        $query = $this->selectQuery
            ->whereIn('status', ['active', 'pending'])
            ->whereNotIn('department', [1, 2, 3, 4])
        ;
        static::assertStringEndsWith(
            'where `status` in (?, ?) and `department` not in (?, ?, ?, ?)',
            $query->toSql()
        );
        static::assertSame(['active', 'pending', 1, 2, 3, 4], $query->getBindings());
    }

    public function testWhereBetween(): void
    {
        $query = $this->selectQuery
            ->whereBetween('created_at', '2025-01-01', '2025-12-31')
            ->whereNotBetween('paid_at', '2025-04-01', '2025-05-01')
        ;
        static::assertStringEndsWith(
            'where `created_at` between ? and ? and `paid_at` not between ? and ?',
            $query->toSql()
        );
        static::assertSame(['2025-01-01', '2025-12-31', '2025-04-01', '2025-05-01'], $query->getBindings());
    }

    public function testWhereEmpty(): void
    {
        $query = $this->selectQuery
            ->where(static fn (WhereClause $w) => $w)
            ->whereRaw('')
        ;
        static::assertSame(
            'select * from `t`',
            $query->toSql()
        );
    }

    // GROUP
    public function testGroupBy(): void
    {
        $q = $this->selectQuery
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
        $q = $this->selectQuery
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
        $q = $this->selectQuery
            ->groupBy()
            ->groupByRaw('   ')
        ;
        static::assertSame(
            'select * from `t`',
            $q->toSql()
        );
    }

    // HAVING
    public function testHavingBasic(): void
    {
        $query = $this->selectQuery
            ->having('is_paid', 1)
            ->having('category', '!=', 'mobile')
            ->having('created_at', '>', '2025-02-12')
            ->orHaving('status', 'closed')
        ;
        static::assertStringEndsWith(
            'having `is_paid` = ? and `category` != ? and (`created_at` > ? or `status` = ?)',
            $query->toSql()
        );
        static::assertSame([1, 'mobile', '2025-02-12', 'closed'], $query->getBindings());
    }

    public function testHavingNested(): void
    {
        $query = $this->selectQuery
            ->having(
                static fn (HavingClause $h) => $h
                    ->having('status', 'in', [1, 2, 3, 4])
                    ->having(
                        static fn (HavingClause $h) => $h
                            ->having('force', 1)
                            ->orHaving('manual', true)
                    )
            )
            ->having('name', 'like', 'iphone%')
        ;
        static::assertStringEndsWith(
            'having (`status` in (?, ?, ?, ?) and (`force` = ? or `manual` = ?)) and `name` like ?',
            $query->toSql()
        );
        static::assertSame([1, 2, 3, 4, 1, true, 'iphone%'], $query->getBindings());
    }

    public function testHavingRaw(): void
    {
        $query = $this->selectQuery
            ->havingRaw('  count(user_id) = ? and YEAR(max_date) = ?', ['paid', 1, 1, 2025])
        ;
        static::assertStringEndsWith(
            'having count(user_id) = ? and YEAR(max_date) = ?',
            $query->toSql()
        );
        static::assertSame(['paid', 1, 1, 2025], $query->getBindings());
    }

    public function testHavingEmpty(): void
    {
        $q = $this->selectQuery
            ->where('id', '>', 10)
            ->groupBy('id')
            ->having(fn (HavingClause $h) => $h)
            ->havingRaw('   ')
        ;
        static::assertStringEndsWith(
            'where `id` > ? group by `id`',
            $q->toSql()
        );
        static::assertSame([10], $q->getBindings());
    }

    // ORDER
    public function testBasicOrderBy(): void
    {
        $q = $this->selectQuery
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
        $q = $this->selectQuery
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
        $q = $this->selectQuery
            ->orderByRaw('  rand()   ')
        ;
        static::assertStringEndsWith(
            'order by rand()',
            $q->toSql()
        );
    }

    public function testOrderByEmpty(): void
    {
        $q = $this->selectQuery
            ->orderByRaw('   ', [1, 2, 3])
        ;
        static::assertSame(
            'select * from `t`',
            $q->toSql()
        );
    }

    // LIMIT OFFSET
    public function testBasicLimit(): void
    {
        $q = $this->selectQuery
            ->limit(5)
        ;
        static::assertSame(
            'select * from `t` limit 5',
            $q->toSql()
        );
    }

    public function testNegativeLimit(): void
    {
        $this->expectException(QueryBuilderException::class);
        $this->expectExceptionMessage('Limit should be greater than 0');
        $q = $this->selectQuery
            ->limit(-5)
        ;
    }

    public function testBasicOffset(): void
    {
        $q = $this->selectQuery
            ->offset(5)
        ;
        static::assertSame(
            'select * from `t` offset 5',
            $q->toSql()
        );
    }

    public function testNegativeOffset(): void
    {
        $this->expectException(QueryBuilderException::class);
        $this->expectExceptionMessage('Offset should be greater than 0');
        $this->selectQuery
            ->offset(-5)
        ;
    }
}
