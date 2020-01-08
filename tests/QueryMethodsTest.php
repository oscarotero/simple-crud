<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Database;

class QueryMethodsTest extends AbstractTestCase
{
    private function createDatabase(): Database
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE
);
EOT
        ]);
    }

    public function testDatabase(): Database
    {
        $db = $this->createDatabase();

        $this->assertInstanceOf(Database::class, $db);

        return $db;
    }

    /**
     * @depends testDatabase
     */
    public function testSelect(Database $db)
    {
        $query = $db->post->select()
            ->distinct()
            ->forUpdate()
            ->from('bar as b')
            ->columns('COUNT(*) as foo_count')
            ->join('LEFT', 'doom AS d', 'foo.id = d.foo_id')
            ->catJoin(' AND d.created = 1')
            ->where('foo = bar')
            ->orWhere('foo is NULL')
            ->catWhere(' and bar < ', 3)
            ->having('foo = 0')
            ->orHaving('foo = 1')
            ->catHaving(' AND foo = 2')
            ->groupBy('post', 'b')
            ->limit(5)
            ->setFlag('HIGH_PRIORITY')
            ->offset(10)
            ->orderBy('foo');

        $this->assertEquals(
            (string) $query,
<<<'SQL'
SELECT DISTINCT HIGH_PRIORITY
    `post`.`id`,
    COUNT(*) as foo_count
FROM
    `post`,
    bar as b
        LEFT JOIN doom AS d ON foo.id = d.foo_id AND d.created = 1
WHERE
    foo = bar
    OR foo is NULL and bar < :__1__
GROUP BY
    post,
    b
HAVING
    foo = 0
    OR foo = 1 AND foo = 2
ORDER BY
    foo
LIMIT 5 OFFSET 10
FOR UPDATE
SQL
        );
    }

    /**
     * @depends testDatabase
     */
    public function testInsert(Database $db)
    {
        $query = $db->post->insert()
            ->setFlag('HIGH_PRIORITY')
            ->set('id', 'NOW()');

        $this->assertEquals(
            (string) $query,
<<<'SQL'
INSERT HIGH_PRIORITY INTO `post` (
    "id"
) VALUES (
    NOW()
)
SQL
        );
    }

    /**
     * @depends testDatabase
     */
    public function testUpdate(Database $db)
    {
        $query = $db->post->update()
            ->setFlag('HIGH_PRIORITY')
            ->set('id', 'NOW()')
            ->where('id = 3')
            ->orWhere('id = 4')
            ->catWhere(' AND id = 5')
            ->orderBy('foo')
            ->limit(5)
            ->offset(6);

        $this->assertEquals(
            (string) $query,
<<<'SQL'
UPDATE HIGH_PRIORITY `post`
SET
    "id" = NOW()
WHERE
    id = 3
    OR id = 4 AND id = 5
SQL
        );
    }

    /**
     * @depends testDatabase
     */
    public function testDelete(Database $db)
    {
        $query = $db->post->delete()
            ->setFlag('HIGH_PRIORITY')
            ->where('id = 3')
            ->orWhere('id = 4')
            ->catWhere(' AND id = 5')
            ->orderBy('foo')
            ->limit(5)
            ->offset(6);

        $this->assertEquals(
            (string) $query,
<<<'SQL'
DELETE HIGH_PRIORITY FROM `post`
WHERE
    id = 3
    OR id = 4 AND id = 5
SQL
        );
    }

    /**
     * @depends testDatabase
     */
    public function testCount(Database $db)
    {
        $query = $db->post->selectAggregate('count')
            ->distinct()
            ->forUpdate()
            ->from('bar as b')
            ->join('LEFT', 'doom AS d', 'foo.id = d.foo_id')
            ->catJoin(' AND d.created = 1')
            ->where('foo = bar')
            ->orWhere('foo is NULL')
            ->catWhere(' and bar < ', 3)
            ->having('foo = 0')
            ->orHaving('foo = 1')
            ->catHaving(' AND foo = 2')
            ->groupBy('post', 'b')
            ->limit(5)
            ->setFlag('HIGH_PRIORITY')
            ->offset(10)
            ->orderBy('foo');

        $this->assertEquals(
            (string) $query,
<<<'SQL'
SELECT DISTINCT HIGH_PRIORITY
    COUNT(id)
FROM
    `post`,
    bar as b
        LEFT JOIN doom AS d ON foo.id = d.foo_id AND d.created = 1
WHERE
    foo = bar
    OR foo is NULL and bar < :__1__
GROUP BY
    post,
    b
HAVING
    foo = 0
    OR foo = 1 AND foo = 2
ORDER BY
    foo
LIMIT 5 OFFSET 10
FOR UPDATE
SQL
        );
    }

    /**
     * @depends testDatabase
     */
    public function testSum(Database $db)
    {
        $query = $db->post->selectAggregate('sum', 'qty * sale_price', 'total_stock');

        $this->assertEquals(
            (string) $query,
<<<'SQL'
SELECT
    SUM(qty * sale_price) AS `total_stock`
FROM
    `post`
SQL
        );
    }
}
