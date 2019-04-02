<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Database;
use SimpleCrud\Queries\Delete;
use SimpleCrud\Queries\Insert;
use SimpleCrud\Queries\Query;
use SimpleCrud\Queries\Select;
use SimpleCrud\Queries\SelectAggregate;
use SimpleCrud\Queries\Update;

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
    `size`  enum('x-small', 'small', 'medium', 'large', 'x-large'),
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
            [Select::class, 'select', null],
            [Insert::class, 'insert', []],
            [Update::class, 'update', []],
            [Delete::class, 'delete', null],
            [SelectAggregate::class, 'selectAggregate', 'count'],
            [SelectAggregate::class, 'selectAggregate', 'sum'],
            [SelectAggregate::class, 'selectAggregate', 'avg'],
            [SelectAggregate::class, 'selectAggregate', 'min'],
            [SelectAggregate::class, 'selectAggregate', 'max'],
        ];
    }

    /**
     * @dataProvider dataProviderQueries
     * @depends testCreation
     * @param mixed $arg
     */
    public function testQueries(string $className, string $name, $arg, Database $db)
    {
        $query = $db->post->$name($arg);

        $this->assertInstanceOf($className, $query);
        $this->assertInstanceOf(Query::class, $query);
    }

    /**
     * @depends testCreation
     */
    public function testSelect(Database $db)
    {
        $statement = $db->post->select()
            ->one()
            ->where('title IS NOT NULL')
            ->where('id IN ', [1, 2])
            ->where('body = ', 'content')
            ->offset(3)
            ->orderBy('title')
            ->__invoke();

        $this->assertQuery(
            $db,
            [1, 2, 'content'],
<<<'SQL'
SELECT
    `post`.`id`,
    `post`.`title`,
    `post`.`body`,
    `post`.`num`,
    asText(`post`.`point`) as `point`,
    `post`.`size`
FROM
    `post`
WHERE
    title IS NOT NULL
    AND id IN (:__1__, :__2__)
    AND body = :__3__
ORDER BY
    title
LIMIT 1 OFFSET 3
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testSelectPage(Database $db)
    {
        $statement = $db->post->select()
            ->one()
            ->where('title IS NOT NULL')
            ->where('id IN ', [1, 2])
            ->page(2)
            ->perPage(5)
            ->orderBy('title')
            ->__invoke();

        $this->assertQuery(
            $db,
            [1, 2],
<<<'SQL'
SELECT
    `post`.`id`,
    `post`.`title`,
    `post`.`body`,
    `post`.`num`,
    asText(`post`.`point`) as `point`,
    `post`.`size`
FROM
    `post`
WHERE
    title IS NOT NULL
    AND id IN (:__1__, :__2__)
ORDER BY
    title
LIMIT 5 OFFSET 5
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testInsert(Database $db)
    {
        $statement = $db->post->insert([
                'title' => 'Title',
                'body' => 'Body',
                'point' => [222, 333],
            ])
            ->__invoke();

        $this->assertQuery(
            $db,
            ['Title', 'Body'],
<<<'SQL'
INSERT INTO `post` (
    `title`,
    `body`,
    `point`
) VALUES (
    :title,
    :body,
    POINT(222, 333)
)
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testUpdate(Database $db)
    {
        $statement = $db->post->update([
                'title' => 'Title',
                'body' => 'Body',
                'point' => [23, 45],
            ])
            ->where('id = ', 3)
            ->__invoke();

        $this->assertQuery(
            $db,
            ['Title', 'Body', 3],
<<<'SQL'
UPDATE `post`
SET
    `title` = :title,
    `body` = :body,
    `point` = POINT(23, 45)
WHERE
    id = :__1__
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testDelete(Database $db)
    {
        $statement = $db->post->delete()
            ->where('id = ', 3)
            ->__invoke();

        $this->assertQuery(
            $db,
            [3],
<<<'SQL'
DELETE FROM `post`
WHERE
    id = :__1__
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testCount(Database $db)
    {
        $statement = $db->post->selectAggregate('COUNT')
            ->where('id = ', 3)
            ->__invoke();

        $this->assertQuery(
            $db,
            [3],
<<<'SQL'
SELECT
    COUNT(id)
FROM
    `post`
WHERE
    id = :__1__
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testSum(Database $db)
    {
        $statement = $db->post->selectAggregate('SUM')
            ->where('id = ', 3)
            ->__invoke();

        $this->assertQuery(
            $db,
            [3],
<<<'SQL'
SELECT
    SUM(id)
FROM
    `post`
WHERE
    id = :__1__
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testMax(Database $db)
    {
        $statement = $db->post->selectAggregate('max')
            ->where('id = ', 3)
            ->__invoke();

        $this->assertQuery(
            $db,
            [3],
<<<'SQL'
SELECT
    MAX(id)
FROM
    `post`
WHERE
    id = :__1__
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testMin(Database $db)
    {
        $statement = $db->post->selectAggregate('min')
            ->where('id = ', 3)
            ->__invoke();

        $this->assertQuery(
            $db,
            [3],
<<<'SQL'
SELECT
    MIN(id)
FROM
    `post`
WHERE
    id = :__1__
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }

    /**
     * @depends testCreation
     */
    public function testAvg(Database $db)
    {
        $statement = $db->post->selectAggregate('avg')
            ->where('id = ', 3)
            ->__invoke();

        $this->assertQuery(
            $db,
            [3],
<<<'SQL'
SELECT
    AVG(id)
FROM
    `post`
WHERE
    id = :__1__
SQL
        );

        $this->assertInstanceOf('PDOStatement', $statement);
    }
}
