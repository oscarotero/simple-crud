SimpleCrud
==========

[![Build Status](https://travis-ci.org/oscarotero/simplecrud.png?branch=master)](https://travis-ci.org/oscarotero/simplecrud)

Simple PHP library to provide some CRUD functions (Create, Read, Update, Delete) in MySql databases.

Requirements
------------

* PHP 5.4 or newer
* Tested only with MySql with InnoDB


Usage
-----

SimpleCrud has the following classes:

* Manager: Is the main class that stores the database connection and manage all entities
* Entity: Is a class that manage an entity (database table) to select, insert, update, delete rows.
* EntityFactory: The class for the creation of Entity instances.
* Row: Manage the data stored in a row of a table
* RowCollection: Is a collection of rows
* Fields: A class to manage a specific format of data stored in database (for example: in datetime values this class convert the value to mysql format before save)


#### Define the entities:

Create a new entity for each table in the database in a common namespace:

```php
namespace MyApp\Entities;

use SimpleCrud\Entity;

class Posts extends Entity {
	public $table = 'posts';
	public $foreignKey = 'posts_id';
	public $fields = [
		'id',
		'title',
		'text',
		'users_id'
	];
}

class Comments extends Entity {
	public $table = 'comments';
	public $foreignKey = 'comments_id';
	public $fields = [
		'id',
		'text',
		'posts_id',
		'users_id'
	]
}

class Users extends Entity {
	public $table = 'users';
	public $foreignKey = 'users_id';
	public $fields = [
		'id',
		'name'
	]
}
```

SimpleCrud uses the foreignKey field to detect automatically the relation between two entities (RELATION_HAS_ONE / RELATION_HAS_MANY). For example: the foreignKey in Posts is "posts_id" and Comments has a field called "posts_id", so SimpleCrud knows that each comment can have one related post (RELATION_HAS_ONE).

You can define also entities with no values:

```php
class Tags extends Entity {
	//If table is not defined, by default get the class name (tags)
	//If foreignKey is not defined, by default get the table + _id (tags_id)
	//If fields are not defined, get them from the database using a DESCRIBE $table query
}
```

This is usefull in early phases, when the database can change and you don't want edit the entity all the time. You can also use this library with no entities classes and the "autocreate" option enabled (to created them automatically).


#### Init the library

Let's create an instance of the Manager, passing the PDO object with the database connection and an instance of EntityFactory to create the entities. The EntityFactory has some options used on create the entities

```php
use SimpleCrud\Manager;
use SimpleCrud\EntityFactory;

$db = new Manager($PDO, new EntityFactory([
	'namespace' => 'MyApp\\Entities' //The namespace where my entities classes are defined
	'autocreate' => true //Set true to create automatically non defined entities.
]));

//You can access to all entities, they will be instanced on demand:
$db->posts; //Posts entity
```

#### Using the library: Create, Read, Update, Delete

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
$posts = $db->posts->selectBy([45, 34, 98]); //Select various posts by id and returns a rowCollection

//selectBy also can select related elements
$post = $db->posts->selectBy(5);
$comments = $db->comments->selectBy($post); //Select all comments related with this post (comments.posts_id = 5)

//Or even using a rowCollection
$posts = $db->posts->selectBy([5, 6, 7, 8]); //Returns a collection with 4 rows
$comments = $db->comments->selectBy($posts); //Returns a collection with all comments related with these 4 rows

//Making more advanced select:
$post = $db->posts->select("date < :date", [':date' => date('Y-m-d H:i:s')], 'id DESC', 10);
//SELECT * FROM posts WHERE date < :date ORDER BY id DESC LIMIT 10

$post = $db->posts->select("id = :id", [':id' => 45], null, true);
//SELECT * FROM posts WHERE id = :id LIMIT 1
// (*) the difference between limit = 1 and limit = true is that true returns the fetched item and 1 returns an rowCollection with 1 element

//Or build your own select query from scratch:
$post = $db->posts->fetchOne('SELECT * FROM posts WHERE users_id  :users_id LIMIT 1', [':users_id' => 3]);

//Simplecrud accepts arrays in the marks:
$posts = $db->posts->fetchAll('SELECT * FROM posts WHERE users_id IN (:users_id)', [':users_id' => [3, 4, 5]]);

//selectBy accepts also the same arguments than select ($where, $marks, $orderBy, $limit)
$db->comments->selectBy($post, 'pubdate < :pubdate', [':pubdate' => date('Y-m-d H:i:s')], 'pubdate DESC', 10); //Returns all comments related with the post older than now, sorted by pubdate and limit 10


//select and selectBy accepts also two more arguments: $join and $from.
//$join allows select more data for each row.
//$from allows add more tables used, for example in "where"

$posts = $db->posts->select('active = 1', null, 'id DESC', 10, ['users']);
//SELECT {all fields from posts and users} FROM posts LEFT JOIN users ON posts.users_id = users.id WHERE active = 1 ORDER BY id DESC LIMIT 10


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

SimpleCrud provides two methods to convert or validate data before push to database and after pull from the database. You can define this methods in the entity class:

```php
class Posts extends Entity {
	public $table = 'posts';
	public $fields = [
		'id',
		'title',
		'text',
		'pubDate',
		'latestUpdate',
		'users_id'
	];

	public function dataToDatabase (array $data, $new) {
		$data['latestUpdate'] = date('Y-m-d H:i:s');

		if ($new) { //it's an insert
			 $data['pubDate'] = $data['latestUpdate'];
		} else if ($data['pubDate'] instanceof Datetime) {
			$data['pubDate'] = $data['pubDate']->format('Y-m-d H:i:s');
		}

		return $data;
	}

	public function dataFromDatabase (array $data) {
		$data['latestUpdate'] = new DateTime($data['latestUpdate']);
		$data['pubDate'] = new DateTime($data['pubDate']);

		return $data;
	}
}
```

#### Set custom rows and rowCollection classes

The entities can use custom Row or RowCollection classes to create custom methods. You need to configurate the entity and create the classes extending SimpleCrud\Row and SimpleRow\CollectionRow:

```php
use SimpleCrud\Entity;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;

class Posts extends Entity {
	public $rowClass = 'MyCustomRowClass';
	public $rowCollectionClass = 'MyCustomRowCollectionClass';
}

class MyCustomRowClass extends Row {
	public function escapeText () {
		$this->text = htmlspecialchars($this->text);
	}
}

class MyCustomRowCollectionClass extends Row {
	public function getSumIds () {
		$ids = $this->get('id');

		return array_sum($ids);
	}
}
```

Now, you can use this functions in the rows and collections:

```php
$posts = $db->posts->selectBy([1, 2, 3]);

$posts->escapeText(); //Execute escapeText in each row

$total = $posts->getSumIds(); //Returns 6
```

If there is a class in the same namespace than the entity and with the same name ending by Row or RowCollection, this class will be taken as Row/RowCollection custom class. For example:


```php
use SimpleCrud\Entity;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;

class Posts extends Entity {
}

class PostsRow extends Row {
	//row custom methods
}

class PostsRowCollection extends Row {
	//collection custom methods
}
```


#### Lacy loads

SimpleCrud loads automatically the related rows if you call them by the entity name:

```php
$post = $db->posts->selectBy(34); //Get posts by id=34

//Load the comments related with this post
foreach ($post->comments as $comment) {
	echo $comment;
}

//This is the same than:
$comments = $db->comments->selectBy($post);
```

This allows make awesome (and dangerous :D) things like this:

```php
$post = $db->posts->selectBy(34);

$title = $post->comments->users->posts->title;

//Get the comments of the post
//Then the users related with these comments
//Then the posts created by these users
//And finally, the titles of all these posts
```

You can define the way of the lacy loads are executed, creating methods starting by "get" in the row class. The result of the method will be cached in the property.
Lacy loads not only works with relations, but also with any property you want. Just create a method named get[NameOfTheProperty] and that is all.

```php
use SimpleCrud\Entity;
use SimpleCrud\Row;

class Posts extends Entity {
	//Entity methods
}

class PostsRow extends Row {
	public function getComments () {
		//Use $this->manager to access to the manager
		return $this->manager->comments->selectBy($this, "validated = 1");
	}

	public function getLowercaseTitle () {
		return strtolower($this->title);
	}
}

// ...

$post = $db->posts->selectBy(4);

$post->comments; //Execute getComments() methods and save the result in $post->comments
$post->comments; //Access to the cached result instead execute getComments() again
$post->lowercaseTitle; //Execute getLowercaseTitle() and save the result in $post->lowercaseTitle;

//You can execute also non defined "getWhatever" methods, if they match with a related entity:
$users = $post->getUsers(); //This is the same than $users->selectBy($post);
```

The difference between execute ```$post->getUsers()``` or call directly ```$post->users``` is that the second save the result in the property "users" so only is executed the first time. $post->getUsers() accepts also the same arguments than $users->selectBy($post) ($where, $marks, $orderBy, etc).


#### Fields

There are some special classes for manage fields. The purpose of these classes is convert the data between the database and the entity. For example, in mysql the format used to store datetime values is "Y-m-d H:i:s", so the class SimpleCrud\Fields\Datetime converts any string or Datetime instance to this format. This not overwrite the value of the row (you will keep the Datetime instance), only converts the data to be stored. The available fields are:

* Field: It's the default field.
* Datetime: Converts a string or Datetime instance to "Y-M-d H:i:s"
* Date: Converts a string or Datetime instance to "Y-M-d"
* Set: an array of values to a string. For example: ['red', 'blue', 'green'] will be stored as "red,blue,green" in database.

As said at begining, if the fields in the entity are not specified, the EntityFactory use a ```"DESCRIBE `{$table}`"``` to get them and use the mysql Type to decide the Field class used ("date" fields use the Date class, "datetime" the Datetime, etc). If you have the fields defined in the entity, you can specify the format in this way:

```php
use SimpleCrud\Entity;

class Posts extends Entity {
	public $table = 'posts';
	public $foreignKey = 'posts_id';
	public $fields = [
		'id',
		'title',
		'text',
		'pubdate' => 'datetime' //[fieldName => fieldType]
	];
}

//Init the SimpleCrud library
// ...

//Now check the post:
$post = $db->posts->create([
	'title' => 'My post'
	'text' => 'My post text'
]);

//Set the pubdate, we don't care about the datetime format in mysql
$post->pubdate = new Datetime('now');

$post->save();
```

#### Custom fields types

You can create your own fields types or overwrite the existing ones. SimpleCrud will search in the namespace ```[entities-namespace]\Fields\``` for your custom classes. For example:

```php

// - Define the custom fields in the namespace "MyModels\Fields"

use SimpleCrud\Fields;

class Serializable extends Fields\Field {
	public function dataToDatabase ($data) {
		return serialize($data);
	}

	public function dataFromDatabase ($data) {
		return unserialize($data);
	}
}


// - Define entities in the namespace "MyModels"

use SimpleCrud\Entity;

class Posts extends Entity {
	public $table = 'posts';
	public $foreignKey = 'posts_id';
	public $fields = [
		'id',
		'text',
		'data' => 'serializable'
	];
}


// - Init the SimpleCrud library providing the namespace where our entities and fields are.

use SimpleCrud\Manager;
use SimpleCrud\EntityFactory;

$db = new Manager($PDO, new EntityFactory([
	'namespace' => 'MyModels'
]));


// - Use it

$post = $db->posts->create();

//Add serializable data, for example an object:
$post->data = new MyDataClass();

//Our custom field serializes the data before save it
$post->save();


//Select a post:
$post = $db->posts->selectBy(34);

return ($post->data instanceOf MyDataClass); //Return true
```

If you create a Field in your namespace with the same name than any of the defaults fields (Date, Datetime, Set, etc), SimpleCrud will choose your custom Field instead the default, so this is useful to overwrite the default behaviours.

Check the commented code to know full API.