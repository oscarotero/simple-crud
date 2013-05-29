SimpleCrud
==========

Simple PHP library to provide some CRUD functions (Create, Read, Update, Delete) in MySql databases.

Requirements
------------

* PHP 5.4 or newer
* Any PSR-0 compatible autoloader


Usage
-----

Configure the database connection and create a class for each table in the database:

```php

use SimpleCrud\Item;

Item::setConnection($PDO);

class Post extends Item {
	public static $table = 'posts'; //The table name
	public static $relation_field = 'posts_id'; //The field used to relate with other tables
	public static $fields = null; //if $fields is empty, it executes a mysql DESCRIBE command to get its names
}

class Comments extends Item {
	public static $table = 'comments';
	public static $relation_field = 'comments_id';
	public static $fields = null;
}
```

Using the library:

```php

//Create a new post

$Post = Post::create([
	'title' => 'My first post',
	'text' => 'This is the text of the post'
]);

//Get/Set values
echo $Post->title; //My first item
$Post->description = 'New description';

//Save (insert/update) the item in the database
$Post->save();

//Select one item
$Post = Post::selectOne('id = :id', [':id' => 45]);

//Select all items
$Posts = Post::selectAll();

//Select some items
$Posts = Post::selectAll('active = 1');

//Select an item from database by id
$Post = Post::selectBy(34);

//Select various items (using an array of ids)
$Posts = Post::selectBy([34, 35, 67]);

//Select an item from database usin a custom key (for example: slug)
$Post = Post::selectBy('my-first-post', 'slug');

//Select items related with other items
$Post = Post::selectBy(35);
$Comments = Comments::selectBy($Post); //Return all comments related with this post

//Fetch all results:
$Post = Post::fetchAll('SELECT * FROM post WHERE title LIKE :title LIMIT 10', [':title' => '%php%']);

//Fetch first result:
$Post = Post::fetch('SELECT * FROM post WHERE title LIKE :title LIMIT 1', [':title' => '%php%']);

//Delete the item
$Post = Post::selectBy(34);
$Post->delete();

//Validate or convert data before saving using the method prepareToSave:
class Post {
	public static $table = 'posts';
	public static $relation_field = 'posts_id';
	public static $fields = null;
	
	public function prepareToSave (array $data) {
		if (!$data['datetime']) {
			$data['datetime'] = date('Y-m-d H:i:s');
		}

		return $data;
	}
}
```

Relations
---------

SimpleCrud provides a simple way to relate/unrelate mysql tables. Each table has a relation_field static variable that define the field name used to join two tables.
Only direct relations are supported, (one-to-many) but not "many-to-many". Example:

```php
$Post = Post::selectById(4);

$Comment = Comments::create([
	'text' => 'This is a comment'
]);

$Comment->join('post', $Post);
$Comment->save();
```

You can select also the related items:

```php
$Post = Post::selectById(4);

$Comments = Comments::selectBy($Post);
```


Lazy properties
---------------

You can define method starting by "get" to return properties in lazy mode:

```php
class Post {
	public static $table = 'posts';
	public static $relation_field = 'posts_id';
	public static $fields = null;
	
	public function getComments () {
		return Comments::selectBy($this);
	}
}

$Post = Post::selectBy(34); //Select a post by id

$comments = $Post->comments; //Execute getComments and return the result

$Post->comments; //Don't execute getComments again. The result has been saved in this property.
```

Join properties
---------------

You can join more than one table on select to optimize the number of mysql queries:

```php
$fields = Users::getQueryFields('author');

$query = "SELECT posts.*, $fields FROM posts LEFT JOIN users ON posts.users_id = users.id WHERE posts.id = :id";

$result = Posts::fetch($query, [':id' => 23]);

echo $result->author->id; //Returns the author id (table users)
```