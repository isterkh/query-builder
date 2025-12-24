<?php

declare(strict_types=1);

namespace Tests\MySql\SelectQuery;

use Isterkh\QueryBuilder\Clauses\WhereClause;

/**
 * @internal
 *
 * @coversNothing
 */
class WhereTest extends SelectQueryTestTemplate
{
    public function testWhereBasic(): void
    {
        $query = $this->query
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
        $query = $this->query
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
        $query = $this->query
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
        $query = $this->query
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
        $query = $this->query
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
        $query = $this->query
            ->where(static fn (WhereClause $w) => $w)
            ->whereRaw('')
        ;
        static::assertSame(
            'select * from `t`',
            $query->toSql()
        );
    }
}
