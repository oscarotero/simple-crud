<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

class RelationsTest extends PHPUnit_Framework_TestCase
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
    `title`       TEXT
);
EOT
            );

            $db->execute(
<<<EOT
CREATE TABLE "category" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`        TEXT
);
EOT
            );

            $db->execute(
<<<EOT
CREATE TABLE "comment" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `text`        TEXT,
    `post_id`     INTEGER
);
EOT
            );

            $db->execute(
<<<EOT
CREATE TABLE "category_post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `category_id` INTEGER NOT NULL,
    `post_id`     INTEGER NOT NULL
);
EOT
            );
        });
    }

    public function testRelatedQuery()
    {
        $db = self::$db;

        $post = $db->post->create(['id' => 1]);
        $comment = $db->comment->create(['id' => 1]);

        $this->assertEquals(
            'SELECT `category`.`id`, `category`.`name`, `category_post`.`post_id` FROM `category`, `category_post`, `post` WHERE (`category_post`.`category_id` = `category`.`id`) AND (`category_post`.`post_id` = `post`.`id`) AND (`post`.`id` IN (:post_id))',
            (string) $post->category()
        );

        $this->assertEquals(
            'SELECT `comment`.`id`, `comment`.`text`, `comment`.`post_id` FROM `comment` WHERE (`comment`.`post_id` = :post_id)',
            (string) $post->comment()
        );

        $this->assertEquals(
            'SELECT `post`.`id`, `post`.`title` FROM `post` WHERE (`post`.`id` = :id) LIMIT 1',
            (string) $comment->post()
        );

        // left join
        $this->assertEquals(
            'SELECT `comment`.`id`, `comment`.`text`, `comment`.`post_id`, `post`.`id` as `post.id`, `post`.`title` as `post.title` FROM `comment` LEFT JOIN `post` ON (`post`.`id` = `comment`.`post_id`)',
            (string) $db->comment->select()->leftJoin('post')
        );

        // id = NULL
        $post = $db->post->create();
        $this->assertEquals(
            'SELECT `comment`.`id`, `comment`.`text`, `comment`.`post_id` FROM `comment` WHERE (`comment`.`post_id` IS NULL)',
            (string) $post->comment()
        );
    }

    public function testDirectRelatedData()
    {
        $db = self::$db;

        $post = $db->post->create([
            'title' => 'first',
        ])->save();

        $this->assertInstanceOf('SimpleCrud\\RowCollection', $post->category);
        $this->assertInstanceOf('SimpleCrud\\RowCollection', $post->comment);

        $comment = $db->comment->create(['text' => 'Hello world']);
        $comment->post = $post;
        $comment->save(true);

        $this->assertSame($post, $comment->post);

        $comment2 = $db->comment->create(['text' => 'Hello world 2']);
        $comment2->post = $post;
        $comment2->save(true);

        $comments = $post->comment;

        $post->clearCache();
        $this->assertCount(2, $post->comment);

        $comment2->post = null;
        $comment2->save(true);

        $post->clearCache();
        $this->assertCount(1, $post->comment);
    }

    public function testManyToManyData()
    {
        $db = self::$db;

        $category1 = $db->category->create(['name' => 'Category 1'])->save();
        $category2 = $db->category->create(['name' => 'Category 2'])->save();

        $post = $db->post->create(['title' => 'second']);

        $categories = $post->category;

        $categories[] = $category1;
        $categories[] = $category2;

        $this->assertCount(2, $post->category);
        $post->save(true);

        $selected = $db->category->select()->run();

        $this->assertEquals((string) $selected, (string) $post->category);
    }

    public function testLeftJoin()
    {
        $db = self::$db;

        $comments = $db->comment->select()
            ->leftJoin('post')
            ->run();

        $this->assertCount(2, $comments);

        $json = '[{"id":1,"text":"Hello world","post_id":1,"post":{"id":1,"title":"first","comment":null}},{"id":2,"text":"Hello world 2","post_id":null}]';

        $this->assertEquals($json, (string) $comments);
    }
}
