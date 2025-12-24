<?php
declare(strict_types=1);


namespace Tests\MySql\SelectQuery;

use Isterkh\QueryBuilder\Clauses\HavingClause;
use Isterkh\QueryBuilder\Clauses\WhereClause;

class HavingTest extends SelectQueryTestTemplate
{
    public function testHavingBasic(): void
    {
        $query = $this->query
            ->having('is_paid', 1)
            ->having('category', '!=', 'mobile')
            ->having('created_at', '>', '2025-02-12')
            ->orHaving('status', 'closed');
        $this->assertStringEndsWith(
            'having `is_paid` = ? and `category` != ? and (`created_at` > ? or `status` = ?)',
            $query->toSql()
        );
        $this->assertSame([1, 'mobile', '2025-02-12', 'closed'], $query->getBindings());
    }

    public function testHavingNested(): void
    {
        $query = $this->query
            ->having(static fn (HavingClause $h) => $h
                ->having('status', 'in', [1,2,3,4])
                ->having(static fn (HavingClause $h) => $h
                    ->having('force', 1)
                    ->orHaving('manual', true)
                )
            )
            ->having('name', 'like', 'iphone%');
        $this->assertStringEndsWith(
            'having (`status` in (?, ?, ?, ?) and (`force` = ? or `manual` = ?)) and `name` like ?',
            $query->toSql()
        );
        $this->assertSame([1, 2, 3, 4, 1, true, 'iphone%'], $query->getBindings());
    }

    public function testHavingRaw(): void
    {
        $query = $this->query
            ->havingRaw('  count(user_id) = ? and YEAR(max_date) = ?', ['paid', 1, 1, 2025]);
        $this->assertStringEndsWith(
            'having count(user_id) = ? and YEAR(max_date) = ?',
            $query->toSql()
        );
        $this->assertSame(['paid', 1, 1, 2025], $query->getBindings());
    }

    public function testHavingEmpty(): void
    {
        $q = $this->query
            ->where('id', '>', 10)
            ->groupBy('id')
            ->having(fn (HavingClause $h) => $h)
            ->havingRaw('   ');
        $this->assertStringEndsWith(
            'where `id` > ? group by `id`',
            $q->toSql()
        );
        $this->assertSame([10], $q->getBindings());
    }

}