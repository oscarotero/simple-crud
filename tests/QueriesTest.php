<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Database;
use function Latitude\QueryBuilder\field;

class QueriesTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createMysqlDatabase([
            'DROP DATABASE IF EXISTS `simple_crud`',
            'CREATE DATABASE `simple_crud`',
            'USE `simple_crud`',
            <<<'SQL'
CREATE TABLE `post` (
    `id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(100) DEFAULT '',
    `body`  text,
    `num`   decimal(10,0) DEFAULT NULL,
    `point` point DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL
        ]);
    }

    public function testCreation(): Database
    {
        $db = $this->createDatabase();

        $this->assertInstanceOf(Database::class, $db);

        return $db;
    }

    public function dataProviderQueries()
    {
        return [
            ['select'],
            ['insert'],
            ['update'],
            ['delete'],
            ['count'],
            ['sum'],
            ['avg'],
            ['min'],
            ['max'],
        ];
    }

    /**
     * @dataProvider dataProviderQueries
     * @depends testCreation
     */
    public function testQueries(string $name, Database $db)
    {
        $query = $db->post->$name();

        $this->assertInstanceOf('SimpleCrud\\Engine\\Mysql\\Query\\'.ucfirst($name), $query);
        $this->assertInstanceOf('SimpleCrud\\Engine\\QueryInterface', $query);
    }

    /**
     * @depends testCreation
     */
    public function testSelect(Database $db)
    {
        $query = $db->post->select()
            ->one()
            ->where('title IS NOT NULL')
            ->where('id IN ', [1, 2])
            ->where('body = ', 'content')
            ->offset(3)
            ->orderBy('title');

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
SELECT
    post.id,
    post.title,
    post.body,
    post.num,
    post.point
FROM
    post
WHERE
    title IS NOT NULL
    AND id IN (:__1__, :__2__)
    AND body = :__3__
ORDER BY
    title
LIMIT 1 OFFSET 3
SQL
            , $sql);

        $this->assertEquals([1, 2, 'content'], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testSelectPage(Database $db)
    {
        $query = $db->post->select()
            ->one()
            ->where('title IS NOT NULL')
            ->where('id IN ', [1, 2])
            ->page(2, 5)
            ->orderBy('title');

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
SELECT
    post.id,
    post.title,
    post.body,
    post.num,
    post.point
FROM
    post
WHERE
    title IS NOT NULL
    AND id IN (:__1__, :__2__)
ORDER BY
    title
LIMIT 5 OFFSET 5
SQL
            , $sql);
        $this->assertEquals([1, 2], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testInsert(Database $db)
    {
        $query = $db->post->insert([
                'title' => 'Title',
                'body' => 'Body',
                'point' => [222, 333],
            ]);
        
        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
INSERT INTO post (
    `title`,
    `body`,
    `point`
) VALUES (
    :title,
    :body,
    POINT(222, 333)
)
SQL
        , $sql);
        $this->assertEquals(['Title', 'Body'], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testUpdate(Database $db)
    {
        $query = $db->post->update([
                'title' => 'Title',
                'body' => 'Body',
                'point' => [23, 45],
            ])
            ->where('id = ', 3);

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
UPDATE post
SET
    `title` = :title,
    `body` = :body,
    `point` = POINT(23, 45)
WHERE
    id = :__1__
SQL
        , $sql);

        $this->assertEquals(['Title', 'Body', 3], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testDelete(Database $db)
    {
        $query = $db->post->delete()
            ->where('id = ', 3);

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
DELETE FROM post
WHERE
    id = :__1__
SQL
        , $sql);

        $this->assertEquals([3], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testCount(Database $db)
    {
        $query = $db->post->count()
            ->where('id = ', 3);

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
SELECT
    COUNT(id)
FROM
    post
WHERE
    id = :__1__
SQL
        , $sql);

        $this->assertEquals([3], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testSum(Database $db)
    {
        $query = $db->post->sum('id')
            ->where('id = ', 3);

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
SELECT
    SUM(id)
FROM
    post
WHERE
    id = :__1__
SQL
        , $sql);

        $this->assertEquals([3], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testMax(Database $db)
    {
        $query = $db->post->max('id')
            ->where('id = ', 3);

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
SELECT
    MAX(id)
FROM
    post
WHERE
    id = :__1__
SQL
        , $sql);

        $this->assertEquals([3], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testMin(Database $db)
    {
        $query = $db->post->min('id')
            ->where('id = ', 3);

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
SELECT
    MIN(id)
FROM
    post
WHERE
    id = :__1__
SQL
        , $sql);

        $this->assertEquals([3], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testAvg(Database $db)
    {
        $query = $db->post->avg('id')
            ->where('id = ', 3);

        $sql = (string) $query;
        $params = $query->getValues();

        $this->assertEquals(<<<'SQL'
SELECT
    AVG(id)
FROM
    post
WHERE
    id = :__1__
SQL
        , $sql);

        $this->assertEquals([3], $params);

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }
}
