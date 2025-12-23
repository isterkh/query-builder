<?php
declare(strict_types=1);

namespace Tests\MySql\SelectQuery;

use Tests\MySql\QueryTestTemplate;
use PHPUnit\Framework\Attributes\DataProvider;

class SimpleSelectTest extends QueryTestTemplate
{
    public static function selectColumnsProvider(): array {
        return [
            'empty string' => ['', 'select *'],
            'empty array' => [[], 'select *'],
            'asterisk' => ['*', 'select *'],
            'array-asterisk' => [['*'], 'select *'],
            'list' => [['a', 'b'], 'select `a`, `b`'],
            'list-alias' => [['a', 'b as c'], 'select `a`, `b` as `c`'],
            'list-array' => [['a', ['b', 'c']], 'select `a`, `b`, `c`']
        ];
    }

    public static function selectFromProvider(): array {
        return [
            'table' => ['table', 'select * from `table`', null],
            'table-alias' => ['table as t', 'select * from `table` as `t`', null],
            'table-alias-param' => ['table', 'select * from `table` as `t`', 't'],
        ];
    }

    public static function selectRawProvider(): array {
        return [
            'without-bindings' => ['user_id, dense_rank() over (partition department_id order by salary desc) as salary_group', []],
            'with-bindings' => ['user_id, dense_rank() over (partition department_id order by salary desc) as salary_group, ? as ext_value', [15]]
        ];
    }
    #[DataProvider('selectColumnsProvider')]
    public function testSelectColumns(
        string|array $columns,
        string $expectedSql,
    ): void {
        $query = $this->builder->select($columns)->from('table');
        $this->assertStringStartsWith(
            $expectedSql,
            $query->toSql()
        );
        $this->assertEmpty($query->getBindings());

    }

    #[DataProvider('selectFromProvider')]
    public function testSelectFrom(
        string $table,
        string $expectedSql,
        ?string $alias = null
    ): void {
        $query = $this->builder->select()->from($table, $alias);
        $this->assertSame(
            $expectedSql,
            $query->toSql()
        );
        $this->assertEmpty($query->getBindings());
    }

    #[DataProvider('selectRawProvider')]
    public function testSelectRaw(
        string $sql,
        array $bindings = [],
    ): void
    {
        $query = $this->builder
            ->selectRaw($sql, $bindings)
            ->from('salaries');
        $result = 'select ' . $sql . ' from `salaries`';
        $this->assertSame($result, $query->toSql());
        $this->assertSame($bindings, $query->getBindings());
    }

    public function testSelectDistinct(): void
    {
        $query = $this->builder
            ->select('a')
            ->distinct()
            ->distinct()
            ->from('table');

        $this->assertSame(
            'select distinct `a` from `table`',
            $query->toSql()
        );
        $this->assertSame([], $query->getBindings());
    }
}