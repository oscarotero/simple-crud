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
        $query = $this->db->post->select()
            ->where('title NOT NULL')
            ->where('type = 1 OR type = 2')
            ->by('category_id', 5)
            ->limit(1)
            ->offset(3)
            ->orderBy('title');

        $this->assertEquals((string) $query, 'SELECT `post`.`id`, `post`.`title`, `post`.`category_id`, `post`.`pubdate`, `post`.`type` FROM `post` WHERE (title NOT NULL) AND (type = 1 OR type = 2) AND (`post`.`category_id` = :category_id) ORDER BY title LIMIT 3, 1');
    }

    public function testInsert()
    {
        $query = $this->db->post->insert()
            ->data([
                'title' => 'Hello world',
                'pubdate' => new Datetime(),
                'type' => 2,
            ]);

        $this->assertEquals((string) $query, 'INSERT INTO `post` (`title`, `pubdate`, `type`) VALUES (:title, :pubdate, :type)');

        $query->duplications();

        $this->assertEquals((string) $query, 'INSERT OR REPLACE INTO `post` (`title`, `pubdate`, `type`) VALUES (:title, :pubdate, :type)');
    }

    public function testUpdate()
    {
        $query = $this->db->post->update()
            ->data([
                'title' => 'Hello world',
                'pubdate' => new Datetime(),
                'type' => 2,
            ])
            ->where('id = 3')
            ->limit(1, true);

        $this->assertEquals((string) $query, 'UPDATE `post` SET `title` = :__title, `pubdate` = :__pubdate, `type` = :__type WHERE (id = 3) LIMIT 1');
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