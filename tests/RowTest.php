<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\TableFactory;
use SimpleCrud\Table;

class RowTest extends PHPUnit_Framework_TestCase
{
    static private $db;

    static public function setUpBeforeClass()
    {
        self::$db = new SimpleCrud(new PDO('sqlite::memory:'));
        
        self::$db->executeTransaction(function ($db) {
            $db->execute(
<<<EOT
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `publishedAt` TEXT,
    `isActive`    INTEGER
);
EOT
            );
        });
    }

    public function testCreate()
    {
        $data = [
            'title' => 'Second post',
            'publishedAt' => new DateTime(),
            'isActive' => true
        ];

        //Test cache        
        $this->assertFalse(isset(self::$db->post[1]));

        self::$db->post[1] = ['title' => 'First post'];

        $this->assertTrue(isset(self::$db->post[1]));

        //Test row
        $post = self::$db->post->create($data);

        $this->assertInstanceOf('SimpleCrud\\Row', $post);

        $this->assertNull($post->id);
        $this->assertSame($data['title'], $post->title);
        $this->assertSame($data['publishedAt'], $post->publishedAt);
        $this->assertSame($data['isActive'], $post->isActive);

        $this->assertFalse(isset(self::$db->post[2]));

        $post->save();

        $this->assertSame(2, $post->id);
        $this->assertTrue(isset(self::$db->post[2]));

        $saved = self::$db->post[2];

        $this->assertSame($saved, $post);

        self::$db->post->clearCache();

        $saved2 = self::$db->post[2];

        $this->assertNotSame($saved2, $post);

        $this->assertEquals($saved2->toArray(), $post->toArray());
    }
}
