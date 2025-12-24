<?php

declare(strict_types=1);

namespace Tests\MySql\SelectQuery;

use Isterkh\QueryBuilder\Exceptions\CompilerException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\MySql\QueryTestTemplate;

/**
 * @internal
 *
 * @coversNothing
 */
class SimpleSelectTest extends QueryTestTemplate
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
        $result = 'select '.$sql.' from `salaries`';
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
}
