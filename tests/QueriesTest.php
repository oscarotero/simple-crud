<?php

use SimpleCrud\SimpleCrud;

class QueriesTest extends PHPUnit_Framework_TestCase
{
    private $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(new PDO('sqlite::memory:'));

        $this->db->executeTransaction(function ($db) {
            $db->execute(
<<<EOT
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `body`        TEXT,
    `num`         REAL
);
EOT
            );
        });
    }

    public function dataProviderQueries()
    {
        return [
            ['count'],
            ['delete'],
            ['insert'],
            ['select'],
            ['sum'],
            ['update'],
        ];
    }

    /**
     * @dataProvider dataProviderQueries
     */
    public function testQueries($name)
    {
        $query = $this->db->post->$name();

        $this->assertInstanceOf('SimpleCrud\\Queries\\Sqlite\\'.ucfirst($name), $query);
        $this->assertInstanceOf('SimpleCrud\\Queries\\Query', $query);
    }

    public function testSelect()
    {
        $query = $this->db->post->select()
            ->one()
            ->where('title NOT NULL')
            ->where('id = 1 OR id = 2')
            ->by('body', 'content')
            ->offset(3)
            ->orderBy('title');

        $this->assertEquals('SELECT `post`.`id`, `post`.`title`, `post`.`body`, `post`.`num` FROM `post` WHERE (title NOT NULL) AND (id = 1 OR id = 2) AND (`post`.`body` = :body) ORDER BY title LIMIT 3, 1', (string) $query);
    }

    public function testInsert()
    {
        $query = $this->db->post->insert()
            ->data([
                'title' => 'Title',
                'body' => 'Body',
            ]);

        $this->assertEquals('INSERT INTO `post` (`title`, `body`) VALUES (:title, :body)', (string) $query);

        $query->duplications();

        $this->assertEquals('INSERT OR REPLACE INTO `post` (`title`, `body`) VALUES (:title, :body)', (string) $query);
    }

    public function testUpdate()
    {
        $query = $this->db->post->update()
            ->data([
                'title' => 'Title',
                'body' => 'Body',
            ])
            ->where('id = 3');

        $this->assertEquals('UPDATE `post` SET `title` = :__title, `body` = :__body WHERE (id = 3)', (string) $query);
    }

    public function testDelete()
    {
        $query = $this->db->post->delete()
            ->where('id = 3');

        $this->assertEquals('DELETE FROM `post` WHERE (id = 3)', (string) $query);
    }

    public function testCount()
    {
        $query = $this->db->post->count()
            ->where('id = 3');

        $this->assertEquals('SELECT COUNT(*) FROM `post` WHERE (id = 3)', (string) $query);
    }

    public function testSum()
    {
        $this->db->post->insert()
            ->data([
                'title' => 'Title',
                'body' => 'Body',
                'num' => 0.3
            ])
            ->run();

        $query = $this->db->post->sum()
            ->field('id')
            ->where('id < 3');

        $this->assertEquals('SELECT SUM(`id`) FROM `post` WHERE (id < 3)', (string) $query);
        $this->assertInternalType('int', $query->run());

        $query = $this->db->post->sum()
            ->field('num');

        $this->assertEquals('SELECT SUM(`num`) FROM `post`', (string) $query);
        $this->assertInternalType('float', $query->run());
    }

    public function testDefaultQueryModifier()
    {
        $this->db->post->addQueryModifier('select', function ($query) {
            $query->where('title NOT NULL');
        });

        $query = $this->db->post->select()->one();

        $this->assertEquals('SELECT `post`.`id`, `post`.`title`, `post`.`body`, `post`.`num` FROM `post` WHERE (title NOT NULL) LIMIT 1', (string) $query);
    }
}
