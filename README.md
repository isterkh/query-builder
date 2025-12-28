# PHP Query Builder
Dependency-free library for writing SQL queries.
Supports raw sql expressions, CTEs, joins, unions, nested conditions (where, having, join)

Work in progress...

## Usage
Create an instance using the factory or [manually](#low-level-setup):

```php
use Isterkh\QueryBuilder\QueryBuilderFactory;

// first create config
$config = [
    'database'  => 'database',
    'driver'    => 'mysql', // Db driver
    'username'  => 'root', // optional
    'password'  => 'password', // optional
    'host'      => 'localhost', // optional
    'port'      => 3306, // optional
    'charset'   => 'utf8mb4', // Optional
    'collation' => 'utf8mb4_unicode_ci', // Optional
    'prefix'    => '', // Table prefix, optional
    'options'   => [ // PDO constructor options, optional
        PDO::ATTR_TIMEOUT => 5,
    ],
];

// pass the config to the factory
$builder = QueryBuilderFactory::make($config);

$builder->select()
    ->from('users')
    ->whereBetween('age', [18, 60])
    ->lazy()
    ->get();

```

## Available methods
SQL examples are generated for MySQL. 
### Selection
```php
$builder->select('name', 'email'); // select `name`, `email` 
$builder->select(['name', 'email']);
$builder->select(['first_name' => 'name', 'email']) // select `first_name` as `name`, `email`
$builder->selectRaw('row_number over() as rn') // select row_number over() as rn
$builder->table('table', 't') // from `table` as `t`
$builder->from('table') // from `table` - alias for table() method
$builder->select('city')->distinct()->from('table') // select distinct `city` from `table`
```

### Joins
```php
$builder->select()
    ->from('a')
    ->join('b', fn(JoinClause $join) => $join->on('a.id', 'b.id')
        ->on(
            fn (JoinClause $subJoin) => $join->on('a.column1', '>', 'b.column1')
                ->orOn('a.column2', '<', 'b.column2')
        )
        ->where('a.age', '>', 18)
        ->orWhere('a.some_flag', 1)
)
```
```mysql
select * from `a` inner join `b` on `a`.`id` = `b`.`id` and (`a`.`column1` > `b`.`column1` or `a`.`column2` < `b`.`column2`) and (`a`.`age` > ? or `a`.`some_flag` = ?)
```
- **`on()`**: Column-to-column comparison (no parameterization)
- **`where()`**: Column-to-value comparison (values are parameterized)

### Where

```php
$builder
    ->where('col', 'value')
    ->orWhere('col1', 'value1')
    ->whereBetween('age', 18, 30)
    ->where(fn (WhereClause $w) => $w->where('col2', '>', 'value2')
        ->where('flag', null)
        ->orWhere('flag', 'flag_value')
        ->whereNotIn('group', [1,2,3,4])
    )
    ->whereRaw('raw_expr between ? and ? ', [1, 3])
```
```mysql
where (`col` = ? or `col1` = ?) and `age` between ? and ? and (`col2` > ? and (`flag` is null or `flag` = ?) and `group` not in (?, ?, ?, ?)) and raw_expr between ? and ?
```

### Group By
```php
$builder->groupBy('a', 'b')
    ->groupByRaw('raw_expr') 
```
```mysql
group by `a`, `b`, raw_expr 
```
### Having
Same as [where clause](#where), but methods are named `having` and `orHaving` respectively.
```php
$builder
    ->havingRaw('count(*) > 10')
    ->having('column', 'value')
    ->having(fn (HavingClause $h) => $h->having('column1', '>', 10)
        ->orHaving('column2', '>', 10)
    )
```
```mysql
having count(*) > 10 and `column` = ? and (`column1` > ? or `column2` > ?)
```

### Order by
```php
$builder->orderBy('a')
    ->orderBy('b', 'desc')
    ->orderByRaw('count(*) desc') 
```
```mysql
order by `a` asc, `b` desc, count(*) desc
```

### Limit/Offset
```php
$builder->limit(5)->offset(5)
```
```mysql
limit 5 offset 5 
```
### Union
Union/UnionAll change the scope of limit/offset/order by:
1. If called before `union()` then applied to the current select query
2. If called after `union()` then applied to the union result

```php
$builder
    ->select()
    ->from('a')
    ->orderBy('name', 'desc')
    ->limit(5)
    ->offset(5)
    ->unionAll(fn (QueryBuilder $q) $q->select()
        ->from('b') 
    )
    ->limit(10)
    ->offset(10)
    ->orderBy('id')
```
```mysql
(select * from `a` order by `name` desc limit 5 offset 5) union all (select * from `b`) order by `id` asc limit 10 offset 10
```

### CTE
You can use [Common table expressions](https://dev.mysql.com/doc/refman/8.4/en/with.html)

```php
$builder
    ->with('cte', fn (QueryBuilder $q) => $q
        ->selectRaw('id, dense_rank() over(partition by salary) as rnk')
        ->from('employees')
    )->select()
    ->from('cte')
    ->where('rnk', '<=', 3)
```
```mysql
with `cte` as (select id, dense_rank() over(partition by salary) as rnk from `employees`) select * from `cte` where `rnk` <= ?
```

### Executing
There are two basic methods: `get()` for fetching data and `execute()` to run some command and get number of affected rows.
If `lazy()` is called before `get()` then generator will be returned. `lazy()` by itself does not execute the query,
you still need to call `get()` to execute the query.

### Update/Delete/Insert
Only simple queries are supported.

The resulting SQL will contain only the CTE and WHERE clause (if present).

The methods themselves do not execute the query. You need to call `execute` method to execute. 

Batch insert is not supported for now. 
```php
$builder
    ->table('a')
    ->where('id', 15)
    ->update([
        'name' => 'NewName'
    ])
    ->execute();

$builder
    ->table('a')
    ->where('id', '>', 10)
    ->delete()
    ->execute();

$builder
    ->table('a')
    ->insert([
        'column' => 'value',
        'column1' => 'value1',
    ]);
```
```mysql
update `a` set `name` = ? where `id` = ?;
delete from `a` where `id` > ?;
insert into `a` (`column`, `column1`) values(?, ?);
```

### Raw expression
```php
$builder
    ->raw('truncate users')
    ->execute()
```

### Transaction
```php
$builder->getConnection()->beginTransaction();
$builder->getConnection()->commit();
$builder->getConnection()->rollback();

```

## Low Level setup
Instead of using `QueryBuilderFactory` you can manually create required objects:

```php
use Isterkh\QueryBuilder\Compilers\Compiler;
use Isterkh\QueryBuilder\Compilers\Grammar\MySqlGrammar;
use Isterkh\QueryBuilder\Connection;
use Isterkh\QueryBuilder\QueryBuilder;

$compiler = new Compiler(new MySqlGrammar())
$connection = new Connection($compiler, new PDO(...))
$builder = new QueryBuilder($connection);
```