<?php
require_once __DIR__.'/../SimpleCrud/autoloader.php';
require_once __DIR__.'/entities.php';

use SimpleCrud\Entity;
use SimpleCrud\Manager;
use SimpleCrud\EntityFactory;


function MyCustomEntitiesLoader ($className) {
	if (strpos($className, 'MyCustomEntities') === 0) {
		$file = __DIR__.'/'.str_replace('\\', '/', $className).'.php';

		if (is_file($file)) {
			require $file;
		}
	}
}

spl_autoload_register('MyCustomEntitiesLoader');

class GeneratorTest extends PHPUnit_Framework_TestCase {
	protected static $db;

	//Init connection before start the test case
	public static function setUpBeforeClass () {
		$dns = 'mysql:host=localhost;dbname=simplecrud_test;charset=UTF8';
		$user = 'root';
		$pass = '';
		$ns = 'MyCustomEntities';

		shell_exec('php ../generator.php -dns "'.$dns.'" -user '.$user.' -path "'.__DIR__.'/MyCustomEntities" -ns MyCustomEntities');

		$db = new Manager(new PDO($dns, $user, $pass), new EntityFactory([
			'namespace' => $ns
		]));

		self::$db = $db;
	}

	public static function tearDownAfterClass () {
		foreach (glob('MyCustomEntities/*.php') as $filename) {
			unlink($filename);
		}

		rmdir('MyCustomEntities');
	}

	public function testGenerator () {
		$db = self::$db;

		//Instances are valid?
		$this->assertInstanceOf('MyCustomEntities\\Categories', $db->categories);
		$this->assertInstanceOf('MyCustomEntities\\CategoriesRow', $db->categories->create());
		$this->assertInstanceOf('MyCustomEntities\\CategoriesRowCollection', $db->categories->createCollection());

		$this->assertInstanceOf('MyCustomEntities\\Customfield', $db->customfield);
		$this->assertInstanceOf('MyCustomEntities\\CustomfieldRow', $db->customfield->create());
		$this->assertInstanceOf('MyCustomEntities\\CustomfieldRowCollection', $db->customfield->createCollection());

		$this->assertInstanceOf('MyCustomEntities\\Fields', $db->fields);
		$this->assertInstanceOf('MyCustomEntities\\FieldsRow', $db->fields->create());
		$this->assertInstanceOf('MyCustomEntities\\FieldsRowCollection', $db->fields->createCollection());

		$this->assertInstanceOf('MyCustomEntities\\Posts', $db->posts);
		$this->assertInstanceOf('MyCustomEntities\\PostsRow', $db->posts->create());
		$this->assertInstanceOf('MyCustomEntities\\PostsRowCollection', $db->posts->createCollection());

		$this->assertInstanceOf('MyCustomEntities\\Tags', $db->tags);
		$this->assertInstanceOf('MyCustomEntities\\TagsRow', $db->tags->create());
		$this->assertInstanceOf('MyCustomEntities\\TagsRowCollection', $db->tags->createCollection());

		$this->assertInstanceOf('MyCustomEntities\\TagsInPosts', $db->tagsInPosts);
		$this->assertInstanceOf('MyCustomEntities\\TagsInPostsRow', $db->tagsInPosts->create());
		$this->assertInstanceOf('MyCustomEntities\\TagsInPostsRowCollection', $db->tagsInPosts->createCollection());

		$this->assertInstanceOf('MyCustomEntities\\Testing', $db->testing);
		$this->assertInstanceOf('MyCustomEntities\\TestingRow', $db->testing->create());
		$this->assertInstanceOf('MyCustomEntities\\TestingRowCollection', $db->testing->createCollection());
	}
}
