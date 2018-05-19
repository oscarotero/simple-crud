<?php

namespace SimpleCrud\Tests;

use SimpleCrud\Database;
use SimpleCrud\Table;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;

class AutoloadRelationsTest extends AbstractTestCase
{
    private function createDatabase(): Database
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

    public function getDatabaseWithData(): Database
    {
        $db = $this->createDatabase();
        
        $db->post[] = ['title' => 'First post'];
        $db->post[] = ['title' => 'Second post'];
        
        $db->comment[] = ['text' => 'First comment', 'post_id' => 1];
        $db->comment[] = ['text' => 'Second comment', 'post_id' => 1];

        $db->category[] = ['name' => 'Category 1'];
        $db->category[] = ['name' => 'Category 2'];

        $db->category_post[] = ['category_id' => 1, 'post_id' => 1];
        $db->category_post[] = ['category_id' => 1, 'post_id' => 2];
        $db->category_post[] = ['category_id' => 2, 'post_id' => 1];
        
        return $db;
    }

    //HasOne + Row + Row
    public function testBuildHasOneSingleRow()
    {
        $db = $this->getDatabaseWithData();

        $post = $db->post[1];

        $comment = $db->comment->select()->one()->relatedWith($post)->cacheWith($post)->run();

        $this->assertInstanceOf(Row::class, $comment);
        $this->assertInstanceOf(Row::class, $comment->getData('post'));
        $this->assertNull($post->getData('comment'));
        $this->assertSame($post, $comment->getData('post'));
    }

    //HasOne + Row + RowCollection
    public function testBuildHasOneSingleRowCollection()
    {
        $db = $this->getDatabaseWithData();

        $posts = $db->post->select()->run();

        $comment = $db->comment->select()->one()->relatedWith($posts)->cacheWith($posts)->run();

        $this->assertInstanceOf(Row::class, $comment);
        $this->assertNull($comment->getData('post'));
        $this->assertNull($posts->getData('comment'));
    }

    //HasOne + RowCollection + Row
    public function testBuildHasOneMultipleRow()
    {
        $db = $this->getDatabaseWithData();

        $post = $db->post[1];

        $comments = $db->comment->select()->relatedWith($post)->cacheWith($post)->run();

        $this->assertInstanceOf(RowCollection::class, $comments);
        $this->assertInstanceOf(RowCollection::class, $post->getData('comment'));
        $this->assertNull($comments->getData('post'));
        $this->assertCount(2, $comments);
        $this->assertSame($comments, $post->getData('comment'));
    }

    //HasOne + RowCollection + RowCollection
    public function testBuildHasOneMultipleRowCollection()
    {
        $db = $this->getDatabaseWithData();

        $posts = $db->post->select()->run();

        $comments = $db->comment->select()->relatedWith($posts)->cacheWith($posts)->run();

        $this->assertInstanceOf(RowCollection::class, $comments);
        $this->assertInstanceOf(RowCollection::class, $comments->getData('post'));
        $this->assertInstanceOf(RowCollection::class, $posts->getData('comment'));
        $this->assertCount(2, $comments);
        $this->assertSame($comments, $posts->getData('comment'));
        $this->assertSame($posts, $comments->getData('post'));
        $this->assertSame($comments[1]->getData('post'), $posts[1]);
        $this->assertSame($comments[2]->getData('post'), $posts[1]);
        $this->assertSame($comments[1], $posts[1]->getData('comment')[1]);
        $this->assertSame($comments[2], $posts[1]->getData('comment')[2]);
        $this->assertNull($posts[2]->getData('comment'));
    }

    //HasMany + Row + Row
    public function testBuildHasManySingleRow()
    {
        $db = $this->getDatabaseWithData();

        $comment = $db->comment[1];

        $post = $db->post->select()->one()->relatedWith($comment)->cacheWith($comment)->run();

        $this->assertInstanceOf(Row::class, $post);
        $this->assertInstanceOf(Row::class, $comment->getData('post'));
        $this->assertNull($post->getData('comment'));
        $this->assertSame($post, $comment->getData('post'));
    }

    //HasMany + Row + RowCollection
    public function testBuildHasManySingleRowCollection()
    {
        $db = $this->getDatabaseWithData();

        $comments = $db->comment->select()->run();

        $post = $db->post->select()->one()->relatedWith($comments)->cacheWith($comments)->run();

        $this->assertInstanceOf(RowCollection::class, $comments);
        $this->assertInstanceOf(Row::class, $post);
        $this->assertSame($comments, $post->getData('comment'));
        $this->assertNull($comments->getData('post'));
    }

    //HasMany + RowCollection + Row
    public function testBuildHasManyMultipleRow()
    {
        $db = $this->getDatabaseWithData();

        $comment = $db->comment[1];

        $posts = $db->post->select()->relatedWith($comment)->cacheWith($comment)->run();

        $this->assertInstanceOf(Row::class, $comment);
        $this->assertInstanceOf(RowCollection::class, $posts);
        $this->assertNull($posts->getData('comment'));
        $this->assertSame($comment->getData('post'), $posts[1]);
        $this->assertSame($comment, $posts[1]->getData('comment'));
    }

    //HasMany + RowCollection + RowCollection
    public function testBuildHasManyMultipleRowCollection()
    {
        $db = $this->getDatabaseWithData();

        $comments = $db->comment->select()->run();

        $posts = $db->post->select()->relatedWith($comments)->cacheWith($comments)->run();

        $this->assertInstanceOf(RowCollection::class, $comments);
        $this->assertInstanceOf(RowCollection::class, $posts);
        $this->assertInstanceOf(RowCollection::class, $posts->getData('comment'));
        $this->assertInstanceOf(RowCollection::class, $comments->getData('post'));
        $this->assertSame($comments, $posts->getData('comment'));
        $this->assertSame($posts, $comments->getData('post'));
        $this->assertSame($comments[1], $posts[1]->getData('comment')[1]);
    }

    //HasManyToMany + Row + Row
    public function testBuildHasManyToManySingleRow()
    {
        $db = $this->getDatabaseWithData();

        $category = $db->category[1];

        $post = $db->post->select()->one()->relatedWith($category)->cacheWith($category)->run();

        $this->assertInstanceOf(Row::class, $category);
        $this->assertInstanceOf(Row::class, $post);
        $this->assertNull($post->getData('category'));
        $this->assertNull($category->getData('posts'));
    }

    //HasManyToMany + Row + RowCollection
    public function testBuildHasManyToManySingleRowCollection()
    {
        $db = $this->getDatabaseWithData();

        $categories = $db->category->select()->run();

        $post = $db->post->select()->one()->relatedWith($categories)->cacheWith($categories)->run();

        $this->assertInstanceOf(RowCollection::class, $categories);
        $this->assertInstanceOf(Row::class, $post);
        $this->assertNull($post->getData('category'));
        $this->assertNull($categories->getData('posts'));
    }

    //HasManyToMany + RowCollection + Row
    public function testBuildHasManyToManyMultipleRow()
    {
        $db = $this->getDatabaseWithData();

        $category = $db->category[1];

        $posts = $db->post->select()->relatedWith($category)->cacheWith($category)->run();

        $this->assertInstanceOf(Row::class, $category);
        $this->assertInstanceOf(RowCollection::class, $posts);
        $this->assertNull($posts->getData('category'));
        $this->assertNull($category->getData('posts'));
    }

    //HasManyToMany + RowCollection + RowCollection
    public function testBuildHasManyToManyMultipleRowCollection()
    {
        $db = $this->getDatabaseWithData();

        $categories = $db->category->select()->run();

        $posts = $db->post->select()->relatedWith($categories)->cacheWith($categories)->run();

        $this->assertInstanceOf(RowCollection::class, $categories);
        $this->assertInstanceOf(RowCollection::class, $posts);
        $this->assertInstanceOf(RowCollection::class, $posts->getData('category'));
        $this->assertInstanceOf(RowCollection::class, $categories->getData('post'));
        $this->assertSame($posts[1]->getData('category')[1], $categories[1]);

    }
}
