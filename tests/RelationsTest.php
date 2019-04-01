<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Database;

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

    public function testCreation(): Database
    {
        $db = $this->createDatabase();

        $db->post[] = ['title' => 'First post'];
        $db->category[] = ['name' => 'First category'];
        $db->comment[] = ['text' => 'Comment', 'post_id' => 1];
        $db->category_post[] = ['category_id' => 1, 'post_id' => 1];

        $this->assertCount(1, $db->post);
        $this->assertCount(1, $db->category);
        $this->assertCount(1, $db->comment);
        $this->assertCount(1, $db->category_post);

        return $db;
    }

    /**
     * @depends testCreation
     */
    public function testHasOneQuery(Database $db)
    {
        $query = $db->comment->select()
            ->relatedWith($db->post[1]);

        $this->assertEquals([1], $query->getValues());
        $this->assertEquals(<<<'SQL'
SELECT
    `comment`.`id`,
    `comment`.`text`,
    `comment`.`post_id`
FROM
    `comment`
WHERE
    `comment`.`post_id` = :__1__
SQL
            , (string) $query
        );

        $query2 = $db->post[1]->comment();
        $this->assertEquals($query->getValues(), $query2->getValues());
        $this->assertEquals((string) $query, (string) $query2);

        $query = $db->comment->select()
            ->relatedWith($db->post);

        $this->assertEmpty($query->getValues());
        $this->assertEquals(<<<'SQL'
SELECT
    `comment`.`id`,
    `comment`.`text`,
    `comment`.`post_id`
FROM
    `comment`
WHERE
    `comment`.`post_id` IS NOT NULL
SQL
            , (string) $query
        );
    }

    /**
     * @depends testCreation
     */
    public function testSelfRelateQuery(Database $db)
    {
        $query = $db->category->select()
            ->relatedWith($db->category[1]);

        $this->assertEquals([1], $query->getValues());
        $this->assertEquals(<<<'SQL'
SELECT
    `category`.`id`,
    `category`.`name`,
    `category`.`category_id`
FROM
    `category`
WHERE
    `category`.`category_id` = :__1__
SQL
            , (string) $query
        );

        $query = $db->category->select()
            ->relatedWith($db->category);

        $this->assertEmpty($query->getValues());
        $this->assertEquals(<<<'SQL'
SELECT
    `category`.`id`,
    `category`.`name`,
    `category`.`category_id`
FROM
    `category`
WHERE
    `category`.`category_id` IS NOT NULL
SQL
            , (string) $query
        );
    }

    /**
     * @depends testCreation
     */
    public function testHasManyQuery(Database $db)
    {
        $query = $db->post->select()
            ->relatedWith($db->comment[1])
            ->one();

        $this->assertEquals([1], $query->getValues());
        $this->assertEquals(<<<'SQL'
SELECT
    `post`.`id`,
    `post`.`title`
FROM
    `post`
        LEFT JOIN `comment` ON `comment`.`post_id` = `post`.`id`
WHERE
    `comment`.`id` = :__1__
LIMIT 1
SQL
            , (string) $query
        );

        $query2 = $db->comment[1]->post();
        $this->assertEquals($query->getValues(), $query2->getValues());
        $this->assertEquals((string) $query, (string) $query2);

        $query = $db->post->select()
            ->relatedWith($db->comment);

        $this->assertEmpty($query->getValues());
        $this->assertEquals(<<<'SQL'
SELECT
    `post`.`id`,
    `post`.`title`
FROM
    `post`
        LEFT JOIN `comment` ON `comment`.`post_id` = `post`.`id`
WHERE
    `comment`.`post_id` IS NOT NULL
SQL
            , (string) $query
        );
    }

    /**
     * @depends testCreation
     */
    public function testHasManyToManyQuery(Database $db)
    {
        $query = $db->category->select()
            ->relatedWith($db->post[1]);

        $this->assertEquals([1], $query->getValues());
        $this->assertEquals(<<<'SQL'
SELECT
    `category`.`id`,
    `category`.`name`,
    `category`.`category_id`
FROM
    `category`
        LEFT JOIN `category_post` ON `category_post`.`category_id` = `category`.`id`
WHERE
    `category_post`.`post_id` = :__1__
SQL
            , (string) $query
        );

        $query2 = $db->post[1]->category();

        $this->assertEquals($query->getValues(), $query2->getValues());
        $this->assertEquals((string) $query, (string) $query2);

        $query = $db->category->select()
            ->relatedWith($db->post);

        $this->assertEmpty($query->getValues());
        $this->assertEquals(<<<'SQL'
SELECT
    `category`.`id`,
    `category`.`name`,
    `category`.`category_id`
FROM
    `category`
        LEFT JOIN `category_post` ON `category_post`.`category_id` = `category`.`id`
WHERE
    `category_post`.`post_id` IS NOT NULL
SQL
            , (string) $query
        );
    }

    public function testRelateOne()
    {
        $db = $this->createDatabase();

        $post = $db->post->create(['title' => 'First post'])->save();
        $comment = $db->comment->create(['text' => 'Comment'])->save();

        //Relate
        $comment->relate($post);

        $this->assertEquals($post->id, $comment->post_id);
        $this->assertSame($post, $comment->post);

        $result = $db->post
            ->select()
            ->one()
            ->relatedWith($comment)
            ->run();

        $this->assertSame($post, $result);

        //Unrelate
        $comment->unrelate($post);

        $this->assertNull($comment->post_id);

        $result = $db->post
            ->select()
            ->one()
            ->relatedWith($comment)
            ->run();

        $this->assertNull($result);

        //Unrelate all
        $comment->relate($post);

        $this->assertEquals($post->id, $comment->post_id);

        $comment->unrelateAll($db->post);

        $this->assertNull($comment->post_id);

        $result = $db->post
            ->select()
            ->one()
            ->relatedWith($comment)
            ->run();

        $this->assertNull($result);
    }

    public function testRelateMany()
    {
        $db = $this->createDatabase();

        $post = $db->post->create(['title' => 'First post'])->save();
        $comment = $db->comment->create(['text' => 'Comment'])->save();

        //Relate
        $post->relate($comment);

        $this->assertEquals($post->id, $comment->post_id);
        $this->assertSame($post, $comment->post);

        $result = $db->comment
            ->select()
            ->relatedWith($post)
            ->run();

        $this->assertCount(1, $result);
        $this->assertSame($comment, $result[1]);

        //Unrelate
        $post->unrelate($comment);

        $this->assertNull($comment->post_id);

        $result = $db->comment
            ->select()
            ->relatedWith($post)
            ->run();

        $this->assertCount(0, $result);

        //Unrelate all
        $comment2 = $db->comment->create(['text' => 'Other comment'])->save();
        $post->relate($comment, $comment2);

        $result = $db->comment
            ->select()
            ->relatedWith($post)
            ->run();

        $this->assertCount(2, $result);

        $post->unrelateAll($db->comment);

        $result = $db->comment
            ->select()
            ->relatedWith($post)
            ->run();

        $this->assertCount(0, $result);
    }

    public function testRelateManyToMany()
    {
        $db = $this->createDatabase();

        $post = $db->post->create(['title' => 'First post'])->save();
        $category = $db->category->create(['name' => 'First category'])->save();

        //Relate
        $post->relate($category);

        $category_post = $db->category_post[1];

        $this->assertNotNull($category_post);
        $this->assertEquals($category_post->post_id, $post->id);
        $this->assertEquals($category_post->category_id, $category->id);
        $this->assertSame($post, $category->post[1]);
        $this->assertSame($post->category[1], $category);

        $result = $db->category
            ->select()
            ->relatedWith($post)
            ->run();

        $this->assertCount(1, $result);
        $this->assertSame($category, $result[1]);

        //Unrelate
        $db->category_post->clearCache();

        $post->unrelate($category);
        $this->assertNull($db->category_post[1]);

        $result = $db->category
            ->select()
            ->relatedWith($post)
            ->run();

        $this->assertCount(0, $result);

        //Unrelate all
        $category2 = $db->category->create(['name' => 'Second category'])->save();
        $post->relate($category, $category2);

        $result = $db->category
            ->select()
            ->relatedWith($post)
            ->run();

        $this->assertCount(2, $result);

        $post->unrelateAll($db->category);

        $result = $db->category
            ->select()
            ->relatedWith($post)
            ->run();

        $this->assertCount(0, $result);
    }
}
