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
        $query = $this->db->select('posts')
            ->where('title NOT NULL')
            ->where('type = 1 OR type = 2')
            ->by('categories_id', 5)
            ->limit(1)
            ->offset(3)
            ->orderBy('title');

        $query2 = $this->db->posts->select()
            ->where('title NOT NULL')
            ->where('type = 1 OR type = 2')
            ->by('categories_id', 5)
            ->limit(1)
            ->offset(3)
            ->orderBy('title');

        $this->assertEquals((string) $query, 'SELECT `posts`.`id`, `posts`.`title`, `posts`.`categories_id`, `posts`.`pubdate`, `posts`.`type` FROM `posts` WHERE (title NOT NULL) AND (type = 1 OR type = 2) AND (`posts`.`categories_id` = :categories_id) ORDER BY title LIMIT 3, 1');
        $this->assertEquals((string) $query, (string) $query2);
    }

    public function testInsert()
    {
        $query = $this->db->insert('posts')
            ->data([
                'title' => 'Hello world',
                'pubdate' => new Datetime(),
                'type' => 2,
            ]);

        $query2 = $this->db->posts->insert()
            ->data([
                'title' => 'Hello world',
                'pubdate' => new Datetime(),
                'type' => 2,
            ]);

        $this->assertEquals((string) $query, 'INSERT INTO `posts` (`title`, `pubdate`, `type`) VALUES (:title, :pubdate, :type)');
        $this->assertEquals((string) $query, (string) $query2);

        $query->duplications();

        $this->assertEquals((string) $query, 'INSERT OR REPLACE INTO `posts` (`title`, `pubdate`, `type`) VALUES (:title, :pubdate, :type)');
    }

    public function testUpdate()
    {
        $query = $this->db->update('posts')
            ->data([
                'title' => 'Hello world',
                'pubdate' => new Datetime(),
                'type' => 2,
            ])
            ->where('id = 3')
            ->limit(1, true);

        $query2 = $this->db->posts->update()
            ->data([
                'title' => 'Hello world',
                'pubdate' => new Datetime(),
                'type' => 2,
            ])
            ->where('id = 3')
            ->limit(1, true);

        $this->assertEquals((string) $query, 'UPDATE `posts` SET `title` = :__title, `pubdate` = :__pubdate, `type` = :__type WHERE (id = 3) LIMIT 1');
        $this->assertEquals((string) $query, (string) $query2);
    }

    public function testDelete()
    {
        $query = $this->db->delete('posts')
            ->where('id = 3')
            ->offset(2, true)
            ->limit(1, true);

        $query2 = $this->db->posts->delete()
            ->where('id = 3')
            ->offset(2, true)
            ->limit(1, true);

        $this->assertEquals((string) $query, 'DELETE FROM `posts` WHERE (id = 3) LIMIT 2, 1');
        $this->assertEquals((string) $query, (string) $query2);
    }
}
