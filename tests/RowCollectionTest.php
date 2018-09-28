<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Row;
use SimpleCrud\RowCollection;

class RowCollectionTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT
);
EOT
            ,
            <<<'EOT'
CREATE TABLE "category" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`        TEXT,
    `category_id` INTEGER
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
            ,
            <<<'EOT'
CREATE TABLE "category_post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `category_id` INTEGER NOT NULL,
    `post_id`     INTEGER NOT NULL
);
EOT
        ]);
    }

    private function createSeededDatabase()
    {
        $db = $this->createDatabase();

        $db->post[] = ['title' => 'First post'];
        $db->post[] = ['title' => 'Second post'];
        $db->post[] = ['title' => 'Third post'];

        $db->comment[] = ['text' => 'Comment 1 of first post', 'post_id' => 1];
        $db->comment[] = ['text' => 'Comment 1 of second post', 'post_id' => 2];
        $db->comment[] = ['text' => 'Comment 2 of second post', 'post_id' => 2];

        $db->category[] = ['name' => 'Category 1'];
        $db->category[] = ['name' => 'Category 2'];
        $db->category[] = ['name' => 'Category 2 - subcategory', 'category_id' => 2];

        $db->category_post[] = ['category_id' => 1, 'post_id' => 1];
        $db->category_post[] = ['category_id' => 2, 'post_id' => 1];
        $db->category_post[] = ['category_id' => 3, 'post_id' => 2];

        return $db;
    }

    public function testRowCollection()
    {
        $db = $this->createSeededDatabase();

        $posts = $db->post->select()->run();

        $this->assertInstanceOf(RowCollection::class, $posts);
        $this->assertInstanceOf(Row::class, $posts[1]);
        $this->assertSame($db->post[1], $posts[1]);
        $this->assertCount(3, $posts);
    }
}
