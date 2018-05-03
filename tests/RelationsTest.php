<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Engine\SchemeInterface;
use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

class RelationsTest extends AbstractTestCase
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

    public function testCreation(): SimpleCrud
    {
        $db = $this->createDatabase();

        $db->post[] = ['title' => 'First post'];
        $db->category[] = ['name' => 'First category'];
        $db->comment[] = ['text' => 'Comment', 'post_id' => 1];
        $db->category_post[] = ['category_id' => 1, 'post_id' => 1];

        $this->assertEquals(1, $db->post->count()->run());
        $this->assertEquals(1, $db->category->count()->run());
        $this->assertEquals(1, $db->comment->count()->run());
        $this->assertEquals(1, $db->category_post->count()->run());

        return $db;
    }

    /**
     * @depends testCreation
     */
    public function testHasOneQuery(SimpleCrud $db)
    {
        $query = $db->comment->select()
            ->relatedWith($db->post[1])
            ->compile();

        $this->assertEquals(
            SchemeInterface::HAS_ONE,
            $db->getScheme()->getRelation($db->comment, $db->post)
        );

        $this->assertEquals([1], $query->params());
        $this->assertEquals(
            'SELECT "comment"."id", "comment"."text", "comment"."post_id" FROM "comment" WHERE "comment"."post_id" = ?',
            $query->sql()
        );
    }

    /**
     * @depends testCreation
     */
    public function testHasManyQuery(SimpleCrud $db)
    {
        $query = $db->post->select()
            ->relatedWith($db->comment[1])
            ->compile();

        $this->assertEquals(
            SchemeInterface::HAS_MANY,
            $db->getScheme()->getRelation($db->post, $db->comment)
        );

        $this->assertEquals([1], $query->params());
        $this->assertEquals(
            'SELECT "post"."id", "post"."title" FROM "post" WHERE "post"."id" = ?',
            $query->sql()
        );
    }

    /**
     * @depends testCreation
     */
    public function testHasManyToManyQuery(SimpleCrud $db)
    {
        $query = $db->category->select()
            ->relatedWith($db->post[1])
            ->compile();

        $this->assertEquals(
            SchemeInterface::HAS_MANY_TO_MANY,
            $db->getScheme()->getRelation($db->category, $db->post)
        );

        $this->assertEquals([1], $query->params());
        $this->assertEquals(
            'SELECT "category"."id", "category"."name", "category"."category_id", "category_post"."post_id" FROM "category", "category_post" WHERE "category_post"."category_id" = "category"."id" AND "category_post"."post_id" = ?',
            $query->sql()
        );
    }

    /**
     * @depends testCreation
     */
    public function testHasOneNullQuery(SimpleCrud $db)
    {
        // id = NULL
        $query = $db->comment->select()
            ->relatedWith($db->post->create())
            ->compile();

        $this->assertEquals(
            'SELECT "comment"."id", "comment"."text", "comment"."post_id" FROM "comment" WHERE "comment"."post_id" = NULL',
            $query->sql()
        );
    }

    public function testRelateOne()
    {
        $db = $this->createDatabase();

        $post = $db->post->create(['title' => 'First post'])->save();
        $category = $db->category->create(['name' => 'First category'])->save();
        $comment = $db->comment->create(['text' => 'Comment'])->save();

        $comment->relate($post);

        $this->assertEquals($post->id, $comment->post_id);
    }

    public function testRelateMany()
    {
        $db = $this->createDatabase();

        $post = $db->post->create(['title' => 'First post'])->save();
        $category = $db->category->create(['name' => 'First category'])->save();
        $comment = $db->comment->create(['text' => 'Comment'])->save();

        $post->relate($comment);

        $this->assertEquals($post->id, $comment->post_id);
    }

    public function _testDirectRelatedData()
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

    public function _testRelatedWithItself()
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

    public function _testManyToManyData()
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

    public function _testRelateUnrelate()
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
