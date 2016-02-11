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
    `category_id` INTEGER,
    `post_id`     INTEGER
);
EOT
            );
        });
    }

    public function testRelationDetection()
    {
        $db = self::$db;

        $this->assertTrue($db->comment->hasOne($db->post));
        $this->assertTrue($db->post->hasMany($db->comment));
        $this->assertFalse($db->comment->hasMany($db->post));
        $this->assertFalse($db->post->hasOne($db->comment));

        $this->assertTrue($db->post->hasMany($db->category));
        $this->assertTrue($db->category->hasMany($db->post));
        $this->assertFalse($db->post->hasOne($db->category));
        $this->assertFalse($db->category->hasOne($db->post));

        $this->assertTrue($db->category_post->hasOne($db->category));
        $this->assertTrue($db->category_post->hasOne($db->post));
        $this->assertFalse($db->category_post->hasMany($db->category));
        $this->assertFalse($db->category_post->hasMany($db->post));

        $bridge = $db->post->getBridge($db->category);
        $this->assertSame($db->category_post, $bridge);
    }

    public function testRelatedData()
    {
        $db = self::$db;

        $post = $db->post->create([
            'title' => 'first',
        ])->save();

        $this->assertInstanceOf('SimpleCrud\\RowCollection', $post->category);
        $this->assertInstanceOf('SimpleCrud\\RowCollection', $post->comment);

        $comment = $db->comment->create(['text' => 'Hello world']);
        $comment->post = $post;
        $comment->save();

        $this->assertSame($post->id, $comment->post_id);
        $this->assertSame($post, $comment->post);

        $comment2 = $db->comment->create(['text' => 'Hello world 2']);
        $comment2->post = $post;
        $comment2->save();

        $comments = $post->comment;

        $post->clearCache();
        $this->assertCount(2, $post->comment);

        $comment2->post = null;
        $comment2->save();

        $post->clearCache();
        $this->assertCount(1, $post->comment);
    }
}
