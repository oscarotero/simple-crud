<?php

namespace SimpleCrud\Tests;

use SimpleCrud\Database;
use SimpleCrud\Table;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;

class AutoloadRelationsTest extends AbstractTestCase
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
    `name`        TEXT
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

    public function testCache()
    {
        $db = $this->createDatabase();
        
        $db->post[] = ['title' => 'First post'];
        $db->post[] = ['title' => 'Second post'];
        $db->comment[] = ['text' => 'First comment', 'post_id' => 1];
        $db->category[] = ['name' => 'Category 1'];
        $db->category_post[] = ['category_id' => 1, 'post_id' => 1];
        $db->category_post[] = ['category_id' => 1, 'post_id' => 2];

        $post = $db->post[1];
        $category = $db->category[1];
        $comment = $db->comment[1];

        $this->assertSame($post, $comment->post);
        

        $this->assertInstanceOf(Row::class, $db->comment[1]->post);
        $this->assertInstanceOf(RowCollection::class, $post->comment);
        $this->assertInstanceOf(RowCollection::class, $post->category);
        $this->assertInstanceOf(RowCollection::class, $category->post);
        $this->assertCount(2, $category->post);
        $this->assertInstanceOf(RowCollection::class, $category->post->category);
    }
}
