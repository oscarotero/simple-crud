<?php
include_once __DIR__.'/../SimpleCrud/autoloader.php';

use SimpleCrud\Manager;
use SimpleCrud\EntityFactory;

class SimpleCrudTest extends PHPUnit_Framework_TestCase {
	protected static $db;

	//Init connection before start the test case
	public static function setUpBeforeClass () {
		//$pdo = new PDO('mysql:dbname=simplecrud_test;host=127.0.0.1;charset=UTF8', 'travis', '');
		$pdo = new PDO('mysql:dbname=simplecrud_test;host=localhost;charset=UTF8', 'root', '');
		$db = new Manager($pdo, new EntityFactory(['autocreate' => true]));

		$db->execute('SET FOREIGN_KEY_CHECKS=0;');
		$db->execute('TRUNCATE posts;');
		$db->execute('TRUNCATE categories;');
		$db->execute('TRUNCATE tags;');
		$db->execute('TRUNCATE tags_in_posts;');
		$db->execute('SET FOREIGN_KEY_CHECKS=1;');

		self::$db = $db;
	}

	public function testAutocreate () {
		$db = self::$db;

		//Instances are created automatically?
		$this->assertInstanceOf('SimpleCrud\\Manager', $db);
		$this->assertInstanceOf('SimpleCrud\\Entity', $db->posts);
		$this->assertInstanceOf('SimpleCrud\\Entity', $db->categories);
		$this->assertInstanceOf('SimpleCrud\\Entity', $db->tags);
		$this->assertInstanceOf('SimpleCrud\\Entity', $db->tags_in_posts);
		$this->assertFalse($db->unexisting_table);

		//Instances have all fields?
		$this->assertCount(3, $db->posts->getFields());
		$this->assertCount(2, $db->categories->getFields());
		$this->assertCount(2, $db->tags->getFields());
		$this->assertCount(3, $db->tags_in_posts->getFields());
	}

	public function testInsert () {
		$db = self::$db;

		//Tables are empty?
		$this->assertSame(0, $db->posts->count());
		$this->assertSame(0, $db->categories->count());
		$this->assertSame(0, $db->tags->count());
		$this->assertSame(0, $db->tags_in_posts->count());

		//Insert some values in the tables
		$db->posts->insert(['title' => 'First post']);
		$db->categories->insert(['name' => 'Category 1']);
		$db->tags->insert(['name' => 'Tag 1']);

		//Each tables must have 1 row
		$this->assertSame(1, $db->posts->count());
		$this->assertSame(1, $db->categories->count());
		$this->assertSame(1, $db->tags->count());
	}

	public function testRow () {
		$db = self::$db;

		//Create a post
		$post = $db->posts->create();

		$this->assertInstanceOf('SimpleCrud\\Row', $post);

		//Check the post have 3 empty fields
		$this->assertCount(3, $post->toArray());

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
	}

	public function testRelations () {
		$db = self::$db;

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
		$this->assertEquals(1, $tag1InPost->id);

		$tag2 = $db->tags->create(['name' => 'Tag 2'])->save();
		$tag2InPost = $db->tags_in_posts->create()->setRelation($post, $tag2)->save();

		$this->assertInstanceOf('SimpleCrud\\Row', $tag2InPost);
		$this->assertEquals($post->id, $tag2InPost->posts_id);
		$this->assertEquals($tag2->id, $tag2InPost->tags_id);
		$this->assertEquals(2, $tag2InPost->id);

		$tags = $post->tags_in_posts->tags;
		$this->assertInstanceOf('SimpleCrud\\RowCollection', $tags);
		$this->assertEquals(2, $tags->count());
		$this->assertEquals(['1', '2'], $tags->id);
	}
}
