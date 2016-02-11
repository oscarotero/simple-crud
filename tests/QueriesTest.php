<?php

use SimpleCrud\SimpleCrud;

class QueriesTest extends PHPUnit_Framework_TestCase
{
    private static $db;

    public static function setUpBeforeClass()
    {
        self::$db = new SimpleCrud(new PDO('sqlite::memory:'));

        self::$db->executeTransaction(function ($db) {
            $db->execute(
<<<EOT
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `body`        TEXT
);
EOT
            );
        });
    }

    public function testSelect()
    {
        $query = self::$db->post->select()
            ->one()
            ->where('title NOT NULL')
            ->where('id = 1 OR id = 2')
            ->by('body', 'content')
            ->offset(3)
            ->orderBy('title');

        $this->assertEquals('SELECT `post`.`id`, `post`.`title`, `post`.`body` FROM `post` WHERE (title NOT NULL) AND (id = 1 OR id = 2) AND (`post`.`body` = :body) ORDER BY title LIMIT 3, 1', (string) $query);
    }

    public function testInsert()
    {
        $query = self::$db->post->insert()
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
        $query = self::$db->post->update()
            ->data([
                'title' => 'Title',
                'body' => 'Body',
            ])
            ->where('id = 3');

        $this->assertEquals('UPDATE `post` SET `title` = :__title, `body` = :__body WHERE (id = 3)', (string) $query);
    }

    public function testDelete()
    {
        $query = self::$db->post->delete()
            ->where('id = 3');

        $this->assertEquals('DELETE FROM `post` WHERE (id = 3)', (string) $query);
    }

    public function testCount()
    {
        $query = self::$db->post->count()
            ->where('id = 3');

        $this->assertEquals('SELECT COUNT(*) FROM `post` WHERE (id = 3)', (string) $query);
    }

    public function testSum()
    {
        $query = self::$db->post->sum()
            ->field('id')
            ->where('id > 3');

        $this->assertEquals('SELECT SUM(`id`) FROM `post` WHERE (id > 3)', (string) $query);
    }
}
