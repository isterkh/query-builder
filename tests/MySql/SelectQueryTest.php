<?php
declare(strict_types=1);


namespace MySql;

use Isterkh\QueryBuilder\Compilers\CompilerStrategy;
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
                ->with(static fn() => new SelectQueryCompiler())
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
}