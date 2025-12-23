<?php
declare(strict_types=1);


namespace Tests\MySql\SelectQuery;

use Isterkh\QueryBuilder\Clauses\JoinClause;

class SelectJoinTest extends SelectQueryTestTemplate
{
    public function testBasicJoins(): void
    {
        $query = $this->query
            ->join('t1', fn(JoinClause $join) => $join->on('t.id', 't1.t_id'))
            ->leftJoin('t2', fn(JoinClause $join) => $join->on('t.id', 't2.t_id'))
            ->rightJoin('t3', fn(JoinClause $join) => $join->on('t.id', 't3.t_id'));

        $this->assertStringEndsWith(
            'inner join `t1` on `t`.`id` = `t1`.`t_id` left join `t2` on `t`.`id` = `t2`.`t_id` right join `t3` on `t`.`id` = `t3`.`t_id`',
            $query->toSql()
        );
        $this->assertEquals([], $query->getBindings());
    }

    public function testConditionalJoins(): void
    {
        $query = $this->query
            ->join('t1', fn(JoinClause $join) => $join
                ->on('t.name', '=', 't1.name')
                ->orOn('t.email', 't1.email')
                ->whereIn('t1.id', [1,2,3])
                ->orWhereIn('t1.pid', [1,2,3])
            );
        $this->assertStringEndsWith(
            'inner join `t1` on (`t`.`name` = `t1`.`name` or `t`.`email` = `t1`.`email`) and (`t1`.`id` in (?, ?, ?) or `t1`.`pid` in (?, ?, ?))',
            $query->toSql()
        );
        $this->assertEquals([1, 2, 3, 1, 2, 3], $query->getBindings());
    }
}