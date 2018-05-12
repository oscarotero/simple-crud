<?php
namespace SimpleCrud\Tests;

use DateTime;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;

class RowTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `isActive`    INTEGER,
    `publishedAt` TEXT
);
EOT
            ,
            <<<'EOT'
CREATE TABLE "comment" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `text`        TEXT,
    `post_id`     INTEGER
);
EOT
        ]);
    }

    public function testRow()
    {
        $db = $this->createDatabase();

        $data = [
            'title' => 'First post',
            'publishedAt' => new DateTime('04-06-2017'),
            'isActive' => true,
        ];

        $post = $db->post->create($data);

        $this->assertInstanceOf(Row::class, $post);

        $this->assertNull($post->id);
        $this->assertSame($data['title'], $post->title);
        $this->assertSame($data['publishedAt'], $post->publishedAt);
        $this->assertSame($data['isActive'], $post->isActive);

        $post->save();

        $this->assertSame(1, $post->id);
        $this->assertTrue(isset($db->post[1]));
        $this->assertSame($post, $db->post[1]);
    }

    public function testRowCollection()
    {
        $db = $this->createDatabase();

        $db->post[] = ['title' => 'First post'];
        $db->post[] = ['title' => 'Second post'];

        $posts = $db->post->select()->run();

        $this->assertInstanceOf(RowCollection::class, $posts);
        $this->assertInstanceOf(Row::class, $posts[1]);
        $this->assertInstanceOf(Row::class, $posts[2]);

        $this->assertCount(2, $posts);
        $this->assertSame($posts[1], $db->post[1]);
        $this->assertSame($posts[2], $db->post[2]);

        $this->assertEquals([1 => 'First post', 2 => 'Second post'], $posts->title);
    }
}
