<?php
use SimpleCrud\Adapters\Mysql;
use SimpleCrud\Adapters\AdapterInterface;
use SimpleCrud\EntityFactory;

class MysqlAutocreateTest extends PHPUnit_Framework_TestCase
{
    public function testConnection()
    {
        $db = new Mysql(initMysqlPdo(), new EntityFactory([
            'autocreate' => true,
        ]));

        $this->assertInstanceOf('SimpleCrud\\Adapters\\AdapterInterface', $db);

        return $db;
    }

    /**
     * @depends testConnection
     */
    public function testAutocreate(AdapterInterface $db)
    {
        //Instances are created automatically?
        $this->assertInstanceOf('SimpleCrud\\Entity', $db->posts);
        $this->assertInstanceOf('SimpleCrud\\Entity', $db->categories);
        $this->assertInstanceOf('SimpleCrud\\Entity', $db->tags);
        $this->assertInstanceOf('SimpleCrud\\Entity', $db->tags_in_posts);

        //Instances have all fields?
        $this->assertCount(7, $db->posts->fields);
        $this->assertCount(2, $db->categories->fields);
        $this->assertCount(2, $db->tags->fields);
        $this->assertCount(3, $db->tags_in_posts->fields);

        return $db;
    }

    /**
     * @depends testAutocreate
     * @expectedException SimpleCrud\SimpleCrudException
     */
    public function testUnexistingTable(AdapterInterface $db) {
        $db->unexisting_table;
    }

    /**
     * @depends testAutocreate
     */
    public function testInsert(AdapterInterface $db)
    {
        //Tables are empty?
        $this->assertSame(0, $db->posts->count());
        $this->assertSame(0, $db->categories->count());
        $this->assertSame(0, $db->tags->count());
        $this->assertSame(0, $db->tags_in_posts->count());

        //Insert some values in the tables
        $db->posts->insert(['title' => 'First post']);
        $db->categories->insert(['name' => 'Category 1']);
        $db->tags->insert(['name' => 'Tag 1']);
        $db->tags_in_posts->insert(['tags_id' => 1, 'posts_id' => 1]);

        //Each tables must have 1 row
        $this->assertSame(1, $db->posts->count());
        $this->assertSame(1, $db->categories->count());
        $this->assertSame(1, $db->tags->count());
        $this->assertSame(1, $db->tags_in_posts->count());

        return $db;
    }

    /**
     * @depends testInsert
     */
    public function testSelect(AdapterInterface $db)
    {
        $result = $db->posts->select();
        $this->assertInstanceOf('SimpleCrud\\RowCollection', $result);
        $this->assertSame(1, $result->count());

        $result = $db->posts->select('id = 0');
        $this->assertInstanceOf('SimpleCrud\\RowCollection', $result);
        $this->assertSame(0, $result->count());

        $result = $db->posts->select('id = :id', [':id' => 1]);
        $this->assertInstanceOf('SimpleCrud\\RowCollection', $result);
        $this->assertSame(1, $result->count());

        $result = $db->posts->select('id = :id', [':id' => 1], null, true);
        $this->assertInstanceOf('SimpleCrud\\Row', $result);

        $result = $db->posts->select('id = :id', [':id' => 2], null, true);
        $this->assertNull($result);

        //Renamed fields
        $result = $db->posts->fetchOne('SELECT title as title2 FROM posts LIMIT 1');
        $this->assertInstanceOf('SimpleCrud\\Row', $result);
        $this->assertEquals('First post', $result->title2);
    }

    /**
     * @depends testInsert
     */
    public function testRow(AdapterInterface $db)
    {
        //Create a post
        $post = $db->posts->create();

        $this->assertInstanceOf('SimpleCrud\\Row', $post);

        $this->assertCount(7, $post->toArray());

        $this->assertNull($post->id);
        $this->assertNull($post->title);
        $this->assertNull($post->categories_id);

        $this->assertNull($post->get('id'));
        $this->assertNull($post->get('title'));
        $this->assertNull($post->get('categories_id'));

        //Check values set/get
        $post->title = 'Second post';

        $this->assertSame('Second post', $post->title);
        $this->assertSame('Second post', $post->get('title'));

        $post->set(['title' => '2º post']);

        $this->assertSame('2º post', $post->title);
        $this->assertSame('2º post', $post->get('title'));

        //Check row saving
        $post->save();
        $this->assertEquals(2, $post->get('id'));
        $this->assertEquals('2º post', $post->title);
        $this->assertNull($post->categories_id);
        $this->assertSame(2, $db->posts->count());

        $post->reload();
        $this->assertEquals(2, $post->get('id'));
        $this->assertEquals('2º post', $post->title);
        $this->assertNull($post->categories_id);
        $this->assertSame(2, $db->posts->count());

        //Check changed values
        $post = $db->posts->create();

        $this->assertFalse($post->changed());
        $this->assertCount(0, $post->get(true, true));

        $post->title = 'Third post';

        $this->assertTrue($post->changed());
        $this->assertCount(1, $post->get(true, true));
        $this->assertEquals(['title' => 'Third post'], $post->get(true, true));

        $post->save();

        $this->assertFalse($post->changed());
        $this->assertCount(0, $post->get(true, true));
        $this->assertCount(7, $post->get(true));
        $this->assertNull($post->categories_id);
        $this->assertEquals('Third post', $post->title);

        $post = $db->posts->selectBy(1);

        $this->assertFalse($post->changed());

        $post->set(['title' => 'New title']);

        $this->assertTrue($post->changed());

        return $db;
    }

    /**
     * @depends testRow
     */
    public function testRelations(AdapterInterface $db)
    {
        //Select the category id=1
        $category = $db->categories->selectBy(1);

        $this->assertInstanceOf('SimpleCrud\\Row', $category);
        $this->assertEquals(1, $category->id);
        $this->assertEquals('Category 1', $category->name);

        //Select the post id=2
        $post = $db->posts->selectBy(2);

        $this->assertInstanceOf('SimpleCrud\\Row', $post);

        //Check relation post - categories (x - 1)
        $post->setRelation($category)->save();

        $this->assertEquals(1, $post->categories_id);
        $this->assertInstanceOf('SimpleCrud\\Row', $post->categories);
        $this->assertEquals(1, $post->categories->id);

        //Check relation categories - post (1 - x)
        $this->assertInstanceOf('SimpleCrud\\RowCollection', $post->categories->posts);
        $this->assertEquals(1, $post->categories->posts->count());
        $this->assertEquals(['2'], $post->categories->posts->id);

        //Check relation post - tags (x - x)
        $tag1 = $db->tags->selectBy(1);
        $tag1InPost = $db->tags_in_posts->create()->setRelation($post, $tag1)->save();

        $this->assertInstanceOf('SimpleCrud\\Row', $tag1InPost);
        $this->assertEquals($post->id, $tag1InPost->posts_id);
        $this->assertEquals($tag1->id, $tag1InPost->tags_id);
        $this->assertEquals(2, $tag1InPost->id);

        $tag2 = $db->tags->create(['name' => 'Tag 2'])->save();
        $tag2InPost = $db->tags_in_posts->create()->setRelation($post, $tag2)->save();

        $this->assertInstanceOf('SimpleCrud\\Row', $tag2InPost);
        $this->assertEquals($post->id, $tag2InPost->posts_id);
        $this->assertEquals($tag2->id, $tag2InPost->tags_id);
        $this->assertEquals(3, $tag2InPost->id);

        $tags = $post->tags_in_posts->tags;
        $this->assertInstanceOf('SimpleCrud\\RowCollection', $tags);
        $this->assertEquals(2, $tags->count());
        $this->assertEquals(['1', '2'], $tags->id);
    }
}
