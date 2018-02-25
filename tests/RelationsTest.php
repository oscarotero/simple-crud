<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

class RelationsTest extends PHPUnit_Framework_TestCase
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
    `title`       TEXT
);
EOT
            );

            $db->execute(
<<<EOT
CREATE TABLE "category" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`        TEXT,
    `category_id` INTEGER
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
        $db = $this->db;

        $post = $db->post->create(['id' => 1])->save();
        $comment = $db->comment->create(['id' => 1])->save();

        $this->assertEquals(
            'SELECT `category`.`id`, `category`.`name`, `category`.`category_id`, `category_post`.`post_id` FROM `category`, `category_post`, `post` WHERE (`category_post`.`category_id` = `category`.`id`) AND (`category_post`.`post_id` = `post`.`id`) AND (`post`.`id` IN (:post_id))',
            (string) $post->category()
        );

        $this->assertEquals(
            'SELECT `comment`.`id`, `comment`.`text`, `comment`.`post_id` FROM `comment` WHERE (`comment`.`post_id` = :post_id)',
            (string) $post->comment()
        );

        $this->assertEquals(
            'SELECT `post`.`id`, `post`.`title` FROM `post` WHERE (`post`.`id` IS NULL) LIMIT 1',
            (string) $comment->post()
        );

        $comment->relate($post);

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
        $db = $this->db;

        $post = $db->post->create([
            'title' => 'first',
        ])->save();

        $this->assertInstanceOf('SimpleCrud\\RowCollection', $post->category);
        $this->assertInstanceOf('SimpleCrud\\RowCollection', $post->comment);

        $comment = $db->comment->create(['text' => 'Hello world']);
        $comment->relate($post);

        $this->assertSame($post, $comment->post);

        $comment2 = $db->comment->create(['text' => 'Hello world 2']);
        $comment2->relate($post);

        $comments = $post->comment;

        $this->assertCount(2, $post->comment);

        $comment2->unrelate($post);

        $this->assertCount(1, $post->comment);

        //Left join
        $comments = $db->comment->select()
            ->leftJoin('post')
            ->run();

        $this->assertCount(2, $comments);

        $json = json_encode([
            [
                'id' => 1,
                'text' => 'Hello world',
                'post_id' => 1,
                'post' => [
                    'id' => 1,
                    'title' => 'first',
                    'category' => [],
                    'comment' => null,
                ],
            ], [
                'id' => 2,
                'text' => 'Hello world 2',
                'post_id' => null,
            ],
        ]);

        $this->assertEquals($json, (string) $comments);
    }

    public function testRelatedWithItself()
    {
        $db = $this->db;

        $a = $db->category->create(['name' => 'A'])->save();
        $b = $db->category->create(['name' => 'B'])->save();
        $a1 = $db->category->create(['name' => 'A1'])->save();
        $a2 = $db->category->create(['name' => 'A2'])->save();
        $a11 = $db->category->create(['name' => 'A11'])->save();
        $a21 = $db->category->create(['name' => 'A21'])->save();

        $a->relate($a1, $a2);
        $a1->relate($a11);
        $a2->relate($a21);

        $this->assertEquals([3 => 'A1', 4 => 'A2'], $a->category->name);
        $this->assertEquals([5 => 'A11'], $a1->category->name);
        $this->assertEquals([6 => 'A21'], $a2->category->name);

        $a1->unrelate($a11);
        $this->assertEmpty($a1->category->name);

        $a->unrelate($a2);
        $this->assertEquals([3 => 'A1'], $a->category->name);
    }

    public function testManyToManyData()
    {
        $db = $this->db;

        $category1 = $db->category->create(['name' => 'Category 1'])->save();
        $category2 = $db->category->create(['name' => 'Category 2'])->save();

        $post = $db->post->create(['title' => 'second']);

        $post->relate($category1, $category2);

        $this->assertCount(2, $post->category);

        $selected = $db->category->select()->run();

        $this->assertEquals((string) $selected, (string) $post->category);
        $this->assertEquals((string) $selected, (string) $db->post->select()->run()->category);
    }

    public function testRelateUnrelate()
    {
        $db = $this->db;

        $post = $db->post->create(['title' => 'Post 1']);

        $comment1 = $db->comment->create(['text' => 'Comment 1']);
        $post->relate($comment1);
        $this->assertSame($post->id, $comment1->post_id);
        $this->assertCount(1, $post->comment);
        $this->assertSame($post, $comment1->post);

        $comment2 = $db->comment->create(['text' => 'Comment 2']);
        $post->relate($comment2);
        $this->assertSame($post->id, $comment2->post_id);
        $this->assertCount(2, $post->comment);
        $this->assertSame($post, $comment2->post);

        $category1 = $db->category->create(['name' => 'Category 1']);
        $post->relate($category1);
        $this->assertCount(1, $post->category_post);
        $this->assertCount(1, $category1->category_post);
        $this->assertCount(1, $category1->post);
        $this->assertCount(1, $post->category);

        $post->unrelate($comment1);
        $this->assertCount(1, $post->comment);
        $this->assertInstanceOf('SimpleCrud\\NullValue', $comment1->post);

        $post->unrelate($category1);
        $this->assertCount(0, $post->category_post);
        $this->assertCount(0, $category1->category_post);
        $this->assertCount(0, $category1->post);
        $this->assertCount(0, $post->category);

        $category2 = $db->category->create(['name' => 'Category 2']);
        $post->unrelate($category2);
        $this->assertCount(0, $post->category_post);
        $this->assertCount(0, $category2->category_post);
        $this->assertCount(0, $category2->post);
        $this->assertCount(0, $post->category);

        $post->unrelateAll($db->comment);
        $this->assertCount(0, $post->comment);
    }
}
