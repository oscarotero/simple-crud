SimpleCrud
==========

Simple PHP library to provide some CRUD functions (Create, Read, Update, Delete) in MySql databases.

Requirements
------------

* PHP 5.4 or newer
* Tested only with mySql with InnoDB


Usage
-----

SimpleCrud has the following classes:

* Manager: Is the main class that stores the database connection and manage all entities
* Entity: Is a class that manage an entity (table) of the database: select, insert, update, delete rows.
* EntityFactory: The class responsive for the creation of Entity instances.
* Row: Manage the data stored in a row of a table
* RowCollection: Is a collection of rows


#### Define the entities:

Create a new entity for each table in the database in a common namespace:

```
namespace MyApp\Entities;

use SimpleCrud\Entity;

class Posts extends Entity {
	protected $table = 'posts';
	protected $fields = [
		'id',
		'title',
		'text',
		'users_id'
	];
}

class Comments extends Entity {
	protected $table = 'comments';
	protected $fields = [
		'id',
		'text',
		'posts_id',
		'users_id'
	]
}

class Users extends Entity {
	protected $table = 'users';
	protected $fields = [
		'id',
		'name'
	]
}
```

#### Init the library

Let's create a instance of the Manager, passing the PDO object with the database connection and a instance of EntityFactory to create the entities. The EntityFactory has some options used on create the entities

```php
use SimpleCrud\Manager;
use SimpleCrud\EntityFactory;

$db = new Manager($PDO, new EntityFactory([
	'namespace' => 'MyApp\\Entities' //The namespace where all entities classes are defined
	'autocreate' => false //Create automatically non defined entities? false by default
]));
```

#### Using the library

```php

//Create a new post

$post = $db->posts->create([
	'title' => 'My first post',
	'text' => 'This is the text of the post'
]);

//Get/set values
echo $post->title; //My first item
$post->description = 'New description';

//Save (insert/update) the item in the database
$post->save();

//selectBy make selects by the primary key:

$post = $db->posts->selectBy(45); //Select by id
$posts = $db->posts->selectBy([45, 34, 98]); //Select various posts

//selectBy also can select related elements
$post = $db->posts->selectBy(5);
$comments = $db->comments->selectBy($post); //Select all comments related with this post


//Making more advanced select:
$post = $db->posts->select("date < :date", [':date' => date('Y-m-d H:i:s')], 'id DESC', 10);
//SELECT * FROM posts WHERE date < :date ORDER BY id DESC LIMIT 10

$post = $db->posts->select("id = :id", [':id' => 45], null, true);
//SELECT * FROM posts WHERE id = :id LIMIT 1
// (*) the difference between limit = 1 and limit = true is that true returns the fetched item and 1 returns an rowCollection with 1 element

//Delete
$post->delete();

//To insert, update or delete rows without select them, use directly the entity:

$db->posts->delete('id > :id', [':id' => 23], 10);
//DELETE FROM `posts` WHERE id > 23 LIMIT 10

$db->posts->update(['text' => 'Hello world'], 'id = :id', [':id' => 23], 1);
//UPDATE `posts` SET `text` = 'Hello world' WHERE id = 23 LIMIT 1

$id = $db->posts->insert(['text' => 'Hello world']);
//INSERT INTO `posts` (`text`) VALUES ('Hello world')
```

#### Validate data

SimpleCrud provides two methods to convert or validate data before save to database and after selection from the database. You can define this methods in the entity class:

```php
class Posts extends Entity {
	protected $table = 'posts';
	protected $fields = [
		'id',
		'title',
		'text',
		'pubDate',
		'latestUpdate',
		'users_id'
	];

	public function prepareDataToSave (array $data, $new) {
		$data['latestUpdate'] = date('Y-m-d H:i:s');

		if ($new) { //its an insert
			 $data['pubDate'] = $data['latestUpdate'];
		} else if ($data['pubDate'] instanceof Datetime) {
			$data['pubDate'] = $data['pubDate']->format('Y-m-d H:i:s');
		}

		return $data;
	}

	public function prepareDataFromSelection (array $data) {
		$data['latestUpdate'] = new DateTime($data['latestUpdate']);
		$data['pubDate'] = new DateTime($data['pubDate']);

		return $data;
	}
}
```
