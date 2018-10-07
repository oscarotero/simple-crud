<?php
namespace SimpleCrud\Tests;

use PDO;
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
        $db->category_post[] = ['category_id' => 3, 'post_id' => 3];

        return $db;
    }

    public function testRowCollection()
    {
        $db = $this->createSeededDatabase();

        $posts = $db->post->select()->run();

        $this->assertSame($db->post, $posts->getTable());
        $this->assertInstanceOf(RowCollection::class, $posts);
        $this->assertInstanceOf(Row::class, $posts[1]);
        $this->assertSame($db->post[1], $posts[1]);
        $this->assertCount(3, $posts);

        $titles = $posts->title;

        $this->assertInternalType('array', $titles);
        $this->assertCount(3, $titles);
        $this->assertSame($posts[3]->title, $titles[3]);

        $posts->title = 'Same title';

        $this->assertSame('Same title', $db->post[1]->title);
        $this->assertSame('Same title', $posts[2]->title);

        $this->assertTrue(isset($posts->title));
        $this->assertTrue(isset($posts[1]));
        $this->assertFalse(isset($posts->foo));
        $this->assertFalse(isset($posts[4]));

        $array = [
            1 => ['id' => 1, 'title' => 'Same title'],
            2 => ['id' => 2, 'title' => 'Same title'],
            3 => ['id' => 3, 'title' => 'Same title'],
        ];

        $this->assertEquals($array, $posts->toArray());

        $this->assertSame(
            'First post',
            $db->execute('SELECT title FROM post WHERE id = 1')->fetch(PDO::FETCH_COLUMN)
        );

        $posts->save();

        $this->assertSame(
            'Same title',
            $db->execute('SELECT title FROM post WHERE id = 1')->fetch(PDO::FETCH_COLUMN)
        );

        $posts->delete();

        $this->assertEquals(
            0,
            $db->execute('SELECT COUNT(*) FROM post')->fetch(PDO::FETCH_COLUMN)
        );
    }

    public function testRowCollectionHasOneRelations()
    {
        $db = $this->createSeededDatabase();

        $posts = $db->post->select()->run();
        $comments = $posts->comment;

        $this->assertCount(3, $posts);
        $this->assertCount(3, $comments);

        $post_1 = $posts[1]->__debugInfo();
        $post_2 = $posts[2]->__debugInfo();
        $post_3 = $posts[3]->__debugInfo();

        $comment_1 = $comments[1]->__debugInfo();
        $comment_2 = $comments[2]->__debugInfo();
        $comment_3 = $comments[3]->__debugInfo();

        $this->assertCount(1, $post_1['data']['comment']);
        $this->assertCount(2, $post_2['data']['comment']);
        $this->assertCount(0, $post_3['data']['comment']);

        $this->assertSame($db->post[1], $comment_1['data']['post']);
        $this->assertSame($db->post[2], $comment_2['data']['post']);
        $this->assertSame($db->post[2], $comment_3['data']['post']);
    }

    public function testRowCollectionHasManyRelations()
    {
        $db = $this->createSeededDatabase();

        $comments = $db->comment->select()->run();
        $posts = $comments->post;

        $this->assertCount(2, $posts);
        $this->assertCount(3, $comments);

        $post_1 = $posts[1]->__debugInfo();
        $post_2 = $posts[2]->__debugInfo();

        $comment_1 = $comments[1]->__debugInfo();
        $comment_2 = $comments[2]->__debugInfo();
        $comment_3 = $comments[3]->__debugInfo();

        $this->assertCount(1, $post_1['data']['comment']);
        $this->assertCount(2, $post_2['data']['comment']);

        $this->assertSame($db->post[1], $comment_1['data']['post']);
        $this->assertSame($db->post[2], $comment_2['data']['post']);
        $this->assertSame($db->post[2], $comment_3['data']['post']);
    }

    public function testRowCollectionHasManyToManyRelations()
    {
        $db = $this->createSeededDatabase();

        $categories = $db->category->select()->run();
        $category_post = $db->category_post->select()->run();
        $posts = $categories->post;

        $this->assertCount(2, $posts);
        $this->assertCount(3, $categories);
        $this->assertCount(3, $category_post);

        $post_1 = $posts[1]->__debugInfo();
        $post_3 = $posts[3]->__debugInfo();

        $category_1 = $categories[1]->__debugInfo();
        $category_2 = $categories[2]->__debugInfo();
        $category_3 = $categories[3]->__debugInfo();

        $category_post_1 = $category_post[1]->__debugInfo();
        $category_post_2 = $category_post[2]->__debugInfo();
        $category_post_3 = $category_post[3]->__debugInfo();

        $this->assertCount(2, $post_1['data']['category']);
        $this->assertCount(1, $post_3['data']['category']);

        $this->assertCount(1, $category_1['data']['post']);
        $this->assertCount(1, $category_2['data']['post']);
        $this->assertCount(1, $category_3['data']['post']);

        $this->assertSame($posts[1], $category_post_1['data']['post']);
        $this->assertSame($posts[1], $category_post_2['data']['post']);
        $this->assertSame($posts[3], $category_post_3['data']['post']);

        $this->assertSame($categories[1], $category_post_1['data']['category']);
        $this->assertSame($categories[2], $category_post_2['data']['category']);
        $this->assertSame($categories[3], $category_post_3['data']['category']);
    }
}
