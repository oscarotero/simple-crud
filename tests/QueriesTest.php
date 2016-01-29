<?php

use SimpleCrud\SimpleCrud;

class QueriesTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(initSqlitePdo());
    }

    public function testSelect()
    {
        $query = $this->db->post->selectOne()
            ->where('title NOT NULL')
            ->where('type = 1 OR type = 2')
            ->by('category_id', 5)
            ->offset(3)
            ->orderBy('title');

        $this->assertEquals((string) $query, 'SELECT `post`.`id`, `post`.`title`, `post`.`category_id`, `post`.`publishedAt`, `post`.`isActive`, `post`.`type` FROM `post` WHERE (title NOT NULL) AND (type = 1 OR type = 2) AND (`post`.`category_id` = :category_id) ORDER BY title LIMIT 3, 1');
    }

    public function testInsert()
    {
        $query = $this->db->post->insert()
            ->data([
                'title' => 'Hello world',
                'publishedAt' => new Datetime(),
                'type' => 2,
            ]);

        $this->assertEquals((string) $query, 'INSERT INTO `post` (`title`, `publishedAt`, `type`) VALUES (:title, :publishedAt, :type)');

        $query->duplications();

        $this->assertEquals((string) $query, 'INSERT OR REPLACE INTO `post` (`title`, `publishedAt`, `type`) VALUES (:title, :publishedAt, :type)');
    }

    public function testUpdate()
    {
        $query = $this->db->post->update()
            ->data([
                'title' => 'Hello world',
                'publishedAt' => new Datetime(),
                'type' => 2,
            ])
            ->where('id = 3')
            ->limit(1, true);

        $this->assertEquals((string) $query, 'UPDATE `post` SET `title` = :__title, `publishedAt` = :__publishedAt, `type` = :__type WHERE (id = 3) LIMIT 1');
    }

    public function testDelete()
    {
        $query = $this->db->post->delete()
            ->where('id = 3')
            ->offset(2, true)
            ->limit(1, true);

        $this->assertEquals((string) $query, 'DELETE FROM `post` WHERE (id = 3) LIMIT 2, 1');
    }
}
