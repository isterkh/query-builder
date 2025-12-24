<?php
declare(strict_types=1);


namespace Tests\MySql\SelectQuery;

use Isterkh\QueryBuilder\Exceptions\QueryBuilderException;

class LimitOffsetTest extends SelectQueryTestTemplate
{
    public function testBasicLimit(): void
    {
        $q = $this->query
            ->limit(5);
        $this->assertSame(
            'select * from `t` limit 5',
            $q->toSql()
        );
    }
    public function testNegativeLimit(): void
    {
        $this->expectException(QueryBuilderException::class);
        $this->expectExceptionMessage('Limit should be greater than 0');
        $q = $this->query
            ->limit(-5);
    }

    public function testBasicOffset(): void
    {
        $q = $this->query
            ->offset(5);
        $this->assertSame(
            'select * from `t` offset 5',
            $q->toSql()
        );
    }
    public function testNegativeOffset(): void
    {
        $this->expectException(QueryBuilderException::class);
        $this->expectExceptionMessage('Offset should be greater than 0');
        $q = $this->query
            ->offset(-5);
    }
}