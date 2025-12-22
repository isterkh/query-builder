<?php
declare(strict_types=1);


namespace MySql;

use Isterkh\QueryBuilder\Compilers\CompilerStrategy;
use Isterkh\QueryBuilder\Compilers\MySql\ConditionsCompiler;
use Isterkh\QueryBuilder\Compilers\MySql\SelectQueryCompiler;
use Isterkh\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

class SelectQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new QueryBuilder(
            new CompilerStrategy()
                ->with(static fn() => new SelectQueryCompiler(new ConditionsCompiler()))
        );
    }

    public function testBasicSelect(): void
    {
        $query = $this->builder
            ->select(['id', 'name', 'email'])
            ->from('users');

        $this->assertEquals(
            'SELECT id, name, email FROM users',
            $query->toSql()
        );
    }

    public function testSelectAll(): void
    {
        $query = $this->builder->select()->from('users');
        $this->assertEquals('SELECT * FROM users', $query->toSql());

        $query = $this->builder->select(['*'])->from('users');
        $this->assertEquals('SELECT * FROM users', $query->toSql());
    }

    public function testSelectWithAlias(): void
    {
        $query = $this->builder->select(['name' => 'full_name', 'u.email'])
            ->from('users', 'u');

        $this->assertEquals(
            'SELECT name AS full_name, u.email FROM users u',
            $query->toSql()
        );
    }

    public function testWhereBasic(): void
    {
        $query = $this->builder->select()
            ->from('users')
            ->where('active', true);

        $this->assertEquals(
            'SELECT * FROM users WHERE active = ?',
            $query->toSql()
        );
        $this->assertEquals([true], $query->getBindings());
    }

    public function testWhereOperators(): void
    {
        $query = $this->builder->select()
            ->from('products')
            ->where('price', '>', 100)
            ->where('category', '!=', 'electronics')
            ->where('stock', '>=', 10);

        $this->assertEquals(
            'SELECT * FROM products WHERE price > ? AND category != ? AND stock >= ?',
            $query->toSql()
        );
        $this->assertEquals([100, 'electronics', 10], $query->getBindings());
    }

    public function testWhereNull(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->where('deleted_at', null);

        $this->assertEquals(
            'SELECT * FROM users WHERE deleted_at IS NULL',
            $query->toSql()
        );
        $this->assertEquals([], $query->getBindings());
    }

    public function testWhereNotNull(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->where('email', '!=', null);

        $this->assertEquals(
            'SELECT * FROM users WHERE email IS NOT NULL',
            $query->toSql()
        );
    }

    public function testWhereIn(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->whereIn('id', [1, 2, 3, 4]);

        $this->assertEquals(
            'SELECT * FROM users WHERE id IN (?, ?, ?, ?)',
            $query->toSql()
        );
        $this->assertEquals([1, 2, 3, 4], $query->getBindings());
    }

    public function testWhereNotIn(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->whereNotIn('status', ['banned', 'pending']);

        $this->assertEquals(
            'SELECT * FROM users WHERE status NOT IN (?, ?)',
            $query->toSql()
        );
    }

    public function testWhereBetween(): void
    {
        $query = $this->builder->select('*')
            ->from('products')
            ->whereBetween('price', 100, 500);

        $this->assertEquals(
            'SELECT * FROM products WHERE price BETWEEN ? AND ?',
            $query->toSql()
        );
        $this->assertEquals([100, 500], $query->getBindings());
    }

    public function testWhereLike(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->where('name', 'LIKE', 'John%');

        $this->assertEquals(
            'SELECT * FROM users WHERE name LIKE ?',
            $query->toSql()
        );
        $this->assertEquals(['John%'], $query->getBindings());
    }

    public function testWhereGroup(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->where(function($q) {
                $q->where('active', true)
                    ->orWhere('verified', true);
            })
            ->where('created_at', '>', '2023-01-01');

        $this->assertEquals(
            'SELECT * FROM users WHERE (active = ? OR verified = ?) AND created_at > ?',
            $query->toSql()
        );
        $this->assertEquals([true, true, '2023-01-01'], $query->getBindings());
    }

    public function testNestedWhereGroups(): void
    {
        $query = $this->builder->select('*')
            ->from('products')
            ->where('category', 'electronics')
            ->where(function($q) {
                $q->where('price', '<', 100)
                    ->orWhere(function($q2) {
                        $q2->where('brand', 'Apple')
                            ->where('rating', '>', 4);
                    });
            });

        $this->assertEquals(
            'SELECT * FROM products WHERE category = ? AND (price < ? OR (brand = ? AND rating > ?))',
            $query->toSql()
        );
    }

    public function testGroupByBasic(): void
    {
        $query = $this->builder->select('category', 'COUNT(*) as total')
            ->from('products')
            ->groupBy('category');

        $this->assertEquals(
            'SELECT category, COUNT(*) as total FROM products GROUP BY category',
            $query->toSql()
        );
    }

    public function testGroupByMultiple(): void
    {
        $query = $this->builder->select('year', 'month', 'COUNT(*) as sales')
            ->from('orders')
            ->groupBy('year', 'month');

        $this->assertEquals(
            'SELECT year, month, COUNT(*) as sales FROM orders GROUP BY year, month',
            $query->toSql()
        );
    }

    public function testGroupByWithExpressions(): void
    {
        $query = $this->builder->select('YEAR(created_at) as year', 'COUNT(*) as total')
            ->from('users')
            ->groupByRaw('YEAR(created_at)');

        $this->assertEquals(
            'SELECT YEAR(created_at) as year, COUNT(*) as total FROM users GROUP BY YEAR(created_at)',
            $query->toSql()
        );
    }

    public function testAggregateFunctions(): void
    {
        $query = $this->builder->select([
            'category',
            'COUNT(*) as total',
            'AVG(price) as avg_price',
            'SUM(quantity) as total_qty',
            'MIN(price) as min_price',
            'MAX(price) as max_price'
        ])
            ->from('products')
            ->groupBy('category');

        $this->assertEquals(
            'SELECT category, COUNT(*) as total, AVG(price) as avg_price, SUM(quantity) as total_qty, MIN(price) as min_price, MAX(price) as max_price FROM products GROUP BY category',
            $query->toSql()
        );
    }

    public function testHavingBasic(): void
    {
        $query = $this->builder->select('category', 'COUNT(*) as total')
            ->from('products')
            ->groupBy('category')
            ->having('total', '>', 10);

        $this->assertEquals(
            'SELECT category, COUNT(*) as total FROM products GROUP BY category HAVING total > ?',
            $query->toSql()
        );
    }

    public function testHavingWithAggregate(): void
    {
        $query = $this->builder->select('category', 'AVG(price) as avg_price')
            ->from('products')
            ->groupBy('category')
            ->having('AVG(price)', '>', 100);

        $this->assertEquals(
            'SELECT category, AVG(price) as avg_price FROM products GROUP BY category HAVING AVG(price) > ?',
            $query->toSql()
        );
    }

    public function testMultipleHaving(): void
    {
        $query = $this->builder->select('user_id', 'COUNT(*) as order_count')
            ->from('orders')
            ->groupBy('user_id')
            ->having('order_count', '>', 5)
            ->having('order_count', '<', 100);

        $this->assertEquals(
            'SELECT user_id, COUNT(*) as order_count FROM orders GROUP BY user_id HAVING order_count > ? AND order_count < ?',
            $query->toSql()
        );
    }

    public function testHavingWithWhere(): void
    {
        $query = $this->builder->select('category', 'COUNT(*) as total')
            ->from('products')
            ->where('active', true)
            ->groupBy('category')
            ->having('total', '>', 5);

        $this->assertEquals(
            'SELECT category, COUNT(*) as total FROM products WHERE active = ? GROUP BY category HAVING total > ?',
            $query->toSql()
        );
    }

    public function testOrderByBasic(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->orderBy('name', 'ASC')
            ->orderBy('created_at', 'DESC');

        $this->assertEquals(
            'SELECT * FROM users ORDER BY name ASC, created_at DESC',
            $query->toSql()
        );
    }

    public function testOrderByRaw(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->orderByRaw('RAND()');

        $this->assertEquals(
            'SELECT * FROM users ORDER BY RAND()',
            $query->toSql()
        );
    }

    public function testOrderByField(): void
    {
        $query = $this->builder->select('*')
            ->from('products')
            ->orderByRaw('FIELD(category, ?, ?, ?)', ['electronics', 'books', 'clothing']);

        $this->assertEquals(
            'SELECT * FROM products ORDER BY FIELD(category, ?, ?, ?)',
            $query->toSql()
        );
        $this->assertEquals(['electronics', 'books', 'clothing'], $query->getBindings());
    }

    public function testLimit(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->limit(10);

        $this->assertEquals('SELECT * FROM users LIMIT 10', $query->toSql());
    }

    public function testOffset(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->offset(20);

        $this->assertEquals('SELECT * FROM users OFFSET 20', $query->toSql());
    }

    public function testLimitOffset(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->limit(10)
            ->offset(20);

        $this->assertEquals('SELECT * FROM users LIMIT 10 OFFSET 20', $query->toSql());
    }

    public function testComplexReportQuery(): void
    {
        $query = $this->builder->select([
            'DATE(created_at) as date',
            'category',
            'COUNT(*) as total_orders',
            'SUM(amount) as total_amount',
            'AVG(amount) as avg_amount'
        ])
            ->from('orders')
            ->where('status', 'completed')
            ->whereBetween('created_at', '2023-01-01', '2023-12-31')
            ->groupBy('DATE(created_at)', 'category')
            ->having('total_orders', '>', 5)
            ->having('total_amount', '>', 1000)
            ->orderBy('date', 'DESC')
            ->orderBy('total_amount', 'DESC')
            ->limit(50);

        $expected = 'SELECT DATE(created_at) as date, category, COUNT(*) as total_orders, SUM(amount) as total_amount, AVG(amount) as avg_amount FROM orders WHERE status = ? AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at), category HAVING total_orders > ? AND total_amount > ? ORDER BY date DESC, total_amount DESC LIMIT 50';

        $this->assertEquals($expected, $query->toSql());
        $this->assertEquals([
            'completed', '2023-01-01', '2023-12-31', 5, 1000
        ], $query->getBindings());
    }

    public function testPaginatedUserQuery(): void
    {
        $query = $this->builder->select('id', 'name', 'email', 'created_at')
            ->from('users')
            ->where('active', true)
            ->where(function($q) {
                $q->where('email_verified', true)
                    ->orWhere('phone_verified', true);
            })
            ->whereNotIn('role', ['banned', 'guest'])
            ->orderBy('created_at', 'DESC')
            ->limit(25)
            ->offset(50);

        $expected = 'SELECT id, name, email, created_at FROM users WHERE active = ? AND (email_verified = ? OR phone_verified = ?) AND role NOT IN (?, ?) ORDER BY created_at DESC LIMIT 25 OFFSET 50';

        $this->assertEquals($expected, $query->toSql());
    }

    public function testSalesAnalysisWithJoins(): void
    {
        $query = $this->builder->select([
            'YEAR(o.order_date) as year',
            'MONTH(o.order_date) as month',
            'p.category',
            'c.country',
            'COUNT(DISTINCT o.id) as order_count',
            'COUNT(DISTINCT o.customer_id) as customer_count',
            'SUM(o.total) as revenue',
            'AVG(o.total) as avg_order_value',
            'MAX(o.total) as max_order',
            'MIN(o.total) as min_order'
        ])
            ->from('orders', 'o')
            ->join('products as p', function($join) {
                $join->on('o.product_id', '=', 'p.id')
                    ->where('p.active', '=', true);
            })
            ->leftJoin('customers as c', function($join) {
                $join->on('o.customer_id', '=', 'c.id')
                    ->where('c.status', '=', 'active');
            })
            ->where('o.status', 'completed')
            ->whereBetween('o.order_date', '2023-01-01', '2023-12-31')
            ->whereIn('p.category', ['electronics', 'books', 'clothing'])
            ->groupBy('YEAR(o.order_date)', 'MONTH(o.order_date)', 'p.category', 'c.country')
            ->having('revenue', '>', 10000)
            ->having('avg_order_value', '>', 50)
            ->orderBy('year', 'DESC')
            ->orderBy('month', 'DESC')
            ->orderBy('revenue', 'DESC')
            ->limit(100)
            ->offset(0);

        $expectedSql = 'SELECT YEAR(o.order_date) as year, MONTH(o.order_date) as month, p.category, c.country, COUNT(DISTINCT o.id) as order_count, COUNT(DISTINCT o.customer_id) as customer_count, SUM(o.total) as revenue, AVG(o.total) as avg_order_value, MAX(o.total) as max_order, MIN(o.total) as min_order FROM orders o INNER JOIN products as p ON o.product_id = p.id AND p.active = ? LEFT JOIN customers as c ON o.customer_id = c.id AND c.status = ? WHERE o.status = ? AND o.order_date BETWEEN ? AND ? AND p.category IN (?, ?, ?) GROUP BY YEAR(o.order_date), MONTH(o.order_date), p.category, c.country HAVING revenue > ? AND avg_order_value > ? ORDER BY year DESC, month DESC, revenue DESC LIMIT 100';

        $this->assertEquals($expectedSql, $query->toSql());
        $this->assertEquals([
            true, 'active', 'completed', '2023-01-01', '2023-12-31',
            'electronics', 'books', 'clothing', 10000, 50
        ], $query->getBindings());
    }
    public function testSearchWithMultipleConditions(): void
    {
        $searchTerm = '%john%';

        $query = $this->builder->select('*')
            ->from('users')
            ->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                    ->orWhere('email', 'LIKE', $searchTerm)
                    ->orWhere('username', 'LIKE', $searchTerm);
            })
            ->where('active', true)
            ->where('created_at', '>', '2020-01-01')
            ->orderBy('last_login', 'DESC')
            ->limit(20);

        $expected = 'SELECT * FROM users WHERE (name LIKE ? OR email LIKE ? OR username LIKE ?) AND active = ? AND created_at > ? ORDER BY last_login DESC LIMIT 20';

        $this->assertEquals($expected, $query->toSql());
        $this->assertEquals([
            '%john%', '%john%', '%john%', true, '2020-01-01'
        ], $query->getBindings());
    }

    public function testEmptySelect(): void
    {
        $query = $this->builder->select()
            ->from('users');
        $this->assertEquals('SELECT * FROM users', $query->toSql());
    }

    public function testBindingsOrder(): void
    {
        $query = $this->builder->select('*')
            ->from('users')
            ->whereIn('id', [1, 2, 3])
            ->where('name', 'John')
            ->whereBetween('age', 20, 30);

        // Bindings should be in order of appearance in SQL
        $this->assertEquals([1, 2, 3, 'John', 20, 30], $query->getBindings());
    }


    public function testSqlInjectionPrevention(): void
    {
        // Проверка, что пользовательский ввод всегда parameterized
        $maliciousInput = "'; DROP TABLE users; --";

        $query = $this->builder->select('*')
            ->from('users')
            ->where('name', $maliciousInput);

        // Должно быть параметризовано, не конкатенировано
        $this->assertEquals(
            'SELECT * FROM users WHERE name = ?',
            $query->toSql()
        );
        $this->assertEquals([$maliciousInput], $query->getBindings());

        // Проверка, что нельзя вставить RAW SQL через обычный where
        $query = $this->builder->select('*')
            ->from('users')
            ->whereRaw("name = '$maliciousInput'"); // Опасно!

        // whereRaw должен явно показывать опасность
        $this->assertEquals(
            "SELECT * FROM users WHERE name = '$maliciousInput'",
            $query->toSql()
        );
    }

    public function testPerformance(): void
    {
        $start = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            $query = $this->builder->select('*')
                ->from('users')
                ->where('active', true)
                ->orderBy('id')
                ->limit(10)
                ->toSql();
        }

        $time = microtime(true) - $start;
        $this->assertLessThan(0.5, $time, 'Should compile 1000 queries in < 0.5s');
    }

    public function testIdentifierQuoting(): void
    {
        // Проверка квотирования зарезервированных слов и спецсимволов
        $query = $this->builder->select('order', 'group', 'table.name')
            ->from('table');

        // Ожидается что-то вроде:
        // SELECT `order`, `group`, `table`.`name` FROM `table`
        $this->assertStringContainsString('`order`', $query->toSql());
        $this->assertStringContainsString('`group`', $query->toSql());
    }
}