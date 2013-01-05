OpenTraits\Crud
===============

OpenTraits is a set of generical and universal PHP traits to give some features to your classes without depending of a specific framework.
It uses the php 5.4 traits feature because this allows to combine different traits in just one class.

This set of traits provides some CRUD functions (Create, Read, Update, Delete).

Requirements
------------

* PHP 5.4 or newer
* Any PSR-0 compatible autoloader

OpenTraits\Crud\Mysql
---------------------

Provide CRUD functions with mysql database. Example:

```php

//Let's create our custom class:

class Item {
	use OpenTraits\Crud\Mysql;

	public static $table = 'item'; //The mysql table name
	public static $fields = null; //if $fields is empty, it executes a mysql DESCRIBE command to get its names
}

//Set the database connection as a PDO object

Item::setConnection($Pdo);

//Create a new item

$Item = Item::create([
	'title' => 'My first item',
	'text' => 'This is a demo of crud'
]);

//Get/Set values
echo $Item->title; //My first item
$Item->description = 'New description';

//Save (insert/update) the item in the database
$Item->save();

//Select an item from database by id
$Item = Item::selectById(34);

//Select one item from database by custom query:
$Item = Item::selectOne(['where' => 'title LIKE :title'], [':title' => 'My first item']);

//Select various items
$query = [
	'where' => [
		'id > :id_start',
		'id < :id_end'
	],
	'sort-by' => 'title',
	'limit' => 2
];
$items = Item::select($query, [':id_start' => 10, ':id_end' => 24]);

//Execute a custom query:
$items = Item::selectByQuery('SELECT * FROM items WHERE title LIKE :title LIMIT 10', [':title' => '%php%']);

//Delete the item
$Item = Item::selectById('34');
$Item->delete();

//Validate or convert data before saving using the method prepareToSave:
class Item {
	use OpenTraits\Crud\Mysql;

	public static $table = 'item';
	public static $fields = null;

	public function prepareToSave (array $data) {
		if ($data['no-save']) {
			die('This item cannot be saved!');
			return false;
		}

		if (!$data['datetime']) {
			$data['datetime'] = date('Y-m-d H:i:s');
		}

		return $data;
	}
}

Item::setDb($Pdo, 'items', ['id', 'title', 'text', 'no-save', 'datetime']);

$Item = Item::create([
	'title' => 'Title',
	'no-save' => true
]);

$Item->save(); //die('This item cannot be saved!')
```

OpenTraits\Crud\Relations
-------------------------

Provide a simple way to relate/unrelate mysql tables. Each table has a relation_field static variable that define the field name used to join two tables.
By now, only supports direct relations, (one-to-one, many-to-one, one-to-many) but not "many-to-many". Example:

```php
class Items {
	use OpenTraits\Crud\Mysql;
	use OpenTraits\Crud\Relations;

	static $table = 'items';
	static $fields = null;
	static $relation_field = 'items_id';
}
class Comments {
	use OpenTraits\Crud\Mysql;
	use OpenTraits\Crud\Relations;

	static $table = 'comments';
	static $fields = null;
	static $relation_field = 'comments_id';
}

$Item = Items::selectById(4);

$Comment = Comments::create([
	'text' => 'This is a comment'
]);

$Comment->relate($Item);

echo $Comment->items_id; //returns 4

$Comment->save();
```

OpenTraits\Crud\Cache
---------------------

Simple cache system. Provides a magic method to execute other methods and save the result in undefined properties. Example:

```php
class Items {
	use OpenTraits\Crud\Mysql;
	use OpenTraits\Crud\Cache;

	static $table = 'comments';
	static $fields = null;

	public function getComments () {
		return Comments::select(['where' => 'items_id = :id'], [':id' => $this->id]);
	}
}

class Comments {
	use OpenTraits\Crud\Mysql;

	static $table = 'comments';
	static $fields = null;
}

$Item = Items::selectById(23);

foreach ($Item->comments as $comment) { //Execute the method getComments and save the result in the property "comments"
	echo $comment->text;
}
```

OpenTraits\Crud\Uploads
-----------------------

Save uploads files and returns the filename. It can save files uploaded by the user ($_FILES) or from url

```php
class Items {
	use OpenTraits\Crud\Mysql;
	use OpenTraits\Crud\Uploads;

	static $table = 'comments';
	static $fields = null;
	static $uploadsPath = '/httpdocs/uploads/';
	static $uploadsUrl = '/uploads/';

	public function prepareToSave (array $data) {
		if ($data['image']) {
			$data['image'] = static::saveFile($data['image'], 'image');
		}

		return $data;
	}
}

$Item = Items::selectById(23);

$Item->image = 'http://lorempixum.com/400/500';
$Item->save();

$Item->image = $_FILES['image'];
$Item->save();
```