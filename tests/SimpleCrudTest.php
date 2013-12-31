<?php
include_once __DIR__.'/../SimpleCrud/autoloader.php';

use SimpleCrud\Manager;
use SimpleCrud\EntityFactory;

class SimpleCrudTest extends PHPUnit_Framework_TestCase {
	protected static $db;

	//Init your app before start the test case
	public static function setUpBeforeClass () {
		$pdo = new PDO('mysql:dbname=test;host=localhost;charset=UTF8', 'root', '');
		$db = new Manager($pdo, new EntityFactory(['autocreate' => true]));

		$db->posts->delete();
		$db->categories->delete();
		$db->tags->delete();
		$db->tags_in_posts->delete();

		self::$db = $db;
	}

	public function testAutocreate () {
		$db = self::$db;

		$this->assertInstanceOf('SimpleCrud\\Manager', $db);
		$this->assertInstanceOf('SimpleCrud\\Entity', $db->posts);
		$this->assertInstanceOf('SimpleCrud\\Entity', $db->categories);
		$this->assertInstanceOf('SimpleCrud\\Entity', $db->tags);
		$this->assertInstanceOf('SimpleCrud\\Entity', $db->tags_in_posts);
		$this->assertFalse($db->unexisting_table);

		$this->assertCount(3, $db->posts->getFields());
		$this->assertCount(2, $db->categories->getFields());
		$this->assertCount(2, $db->tags->getFields());
		$this->assertCount(3, $db->tags_in_posts->getFields());
	}

	public function testInsert () {
		$db = self::$db;

		$this->assertSame(0, $db->posts->count());
		$this->assertSame(0, $db->categories->count());
		$this->assertSame(0, $db->tags->count());
		$this->assertSame(0, $db->tags_in_posts->count());

		$db->posts->insert(['title' => 'First post']);
		$db->categories->insert(['name' => 'Category 1']);
		$db->tags->insert(['name' => 'Tag 1']);

		$this->assertSame(1, $db->posts->count());
		$this->assertSame(1, $db->categories->count());
		$this->assertSame(1, $db->tags->count());
	}

	public function testRow () {
		$db = self::$db;

		$post = $db->posts->create();

		$this->assertInstanceOf('SimpleCrud\\Row', $post);
		$this->assertCount(3, $post->toArray());

		$this->assertNull($post->id);
		$this->assertNull($post->title);
		$this->assertNull($post->categories_id);

		$this->assertNull($post->get('id'));
		$this->assertNull($post->get('title'));
		$this->assertNull($post->get('categories_id'));

		$post->title = 'Second post';

		$this->assertSame('Second post', $post->title);
		$this->assertSame('Second post', $post->get('title'));

		$post->set(['title' => '2º post']);

		$this->assertSame('2º post', $post->title);
		$this->assertSame('2º post', $post->get('title'));

		$post->save();

		$this->assertEquals(2, $post->get('id'));
		$this->assertEquals('2º post', $post->title);
		$this->assertNull($post->categories_id);
	}
}
