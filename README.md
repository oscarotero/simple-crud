SimpleCrud
==========

Simple PHP library to provide some CRUD functions (Create, Read, Update, Delete) in MySql databases.

Requirements
------------

* PHP 5.4 or newer
* Tested only with mySql with InnoDB


Usage
-----

SimpleCrud has two main classes: Item and ItemCollection.

* Manager: Is the main class that stores the database connection and manage all entities
* Entity: Is a class that manage an entity (table) of the database: select, insert, update, delete rows.
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

Let's create a instance of the Manager, passing the PDO object with the database connection and the namespace where the entities are defined:

```php
use SimpleCrud\Manager;

$db = new Manager($PDO, 'MyApp\\Entities');
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


//Making more advanced select:
$post = $db->posts->select("date < :date", [':date' => date('Y-m-d H:i:s')], 'id DESC', 10);
//SELECT * FROM posts WHERE date < :date ORDER BY id DESC LIMIT 10

$post = $db->posts->select("id = :id", [':id' => 45], null, true);
//SELECT * FROM posts WHERE id = :id LIMIT 1
// (*) the difference between limit = 1 and limit = true is that true returns the fetched item and 1 returns an itemCollection with 1 element

//Delete
$post->delete();
```
