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

* Item represents a table of the database and each row of this table.
* ItemCollection is like an array of items (stores the result of a query).

Let's configure the database connection and create a class for each table in the database:

```php
use SimpleCrud\Item;

//Set the database connection to Item, so all tables extending Item share the same configuration.
Item::setConnection($PDO);

class Posts extends Item {
	const TABLE = 'posts'; //The table name
	const FOREIGN_KEY = 'posts_id'; //The field used in other tables to relate with this table

	//And all database fields you want to use
	public $id;
	public $title;
	public $text;
	public $users_id;
}

class Comments extends Item {
	const TABLE = 'comments';
	const FOREIGN_KEY = 'comments_id';
	
	public $id;
	public $text;
	public $posts_id;
	public $users_id;
}

class Users extends Item {
	const TABLE = 'users';
	const FOREIGN_KEY = 'users_id';
	
	public $id;
	public $name;
}
```

Using the library:

```php

//Create a new post

$post = Posts::create([
	'title' => 'My first post',
	'text' => 'This is the text of the post'
]);

//Get/set values
echo $post->title; //My first item
$post->description = 'New description';

//Save (insert/update) the item in the database
$post->save();

//selectBy make selects by id or other keys:

$post = Posts::selectBy(45); //Select by id
$posts = Posts::selectBy([45, 34, 98]); //Select various posts
$users = Users::selectBy($posts); //Select all users related with these posts (the authors)
$posts = Posts::selectBy(45, ['users']); //Select the post id=45 and join the user

//You can also make select in lazy mode:
$post = Posts::selectBy(45);
$comments = $post->comments; //Automatically select all related comments of this post


//Making more advanced select:
$post = Posts::select("date < :date", [':date' => date('Y-m-d H:i:s')], 'id DESC', 10);
//SELECT * FROM posts WHERE date < :date ORDER BY id DESC LIMIT 10

$post = Posts::select("id = :id", [':id' => 45], null, true);
//SELECT * FROM posts WHERE id = :id LIMIT 1
// (*) the difference between limit = 1 and limit = true is that true returns the fetched item and 1 returns an itemCollection with 1 element

//Delete
$post->delete();
```

Validation and callbacks
------------------------

You can define a method to prepare the data before insert/update. If no data is returned, the query won't be executed.

```php
class Posts extends Item {
	const TABLE = 'posts';
	const FOREIGN_KEY = 'posts_id';

	public $id;
	public $title;
	public $pubDate;
	public $latestUpdate;
	public $text;
	public $users_id;

	public function prepareData (array $data, $new) {
		$data['latestUpdate'] = date('Y-m-d H:i:s');

		if ($new) { //is a insert
			$data['pubDate'] = $data['latestUpdate'];
		}

		return $data;
	}
}
```

There are also three callbacks that will be executed on execute a change in database (onInsert, onUpdate and onDelete).
These methods will be invoked in a database transaction just before commit the changes so you can cancel the commit throwing an exception:

```php
class Notifications extends Item {
	const TABLE = 'notifications';
	const FOREIGN_KEY = 'notifications_id';

	public $id;
	public $posts_id;
	public $text;
}

class Posts extends Item {
	const TABLE = 'posts';
	const FOREIGN_KEY = 'posts_id';

	public $id;
	public $title;
	public $pubDate;
	public $latestUpdate;
	public $text;
	public $users_id;

	public function onInsert (array $data) {
		$notification = Notifications::create([
			'text' => 'New post created',
			$this //Relate the notification with this post
		]);

		$notification->save();
	}

	public function onUpdate (array $data) {
		$notification = Notifications::create([
			'text' => 'A post has been updated',
			$this
		]);

		$notification->save();
	}
}
```


Relations
---------

SimpleCrud provides a simple way to relate/unrelate mysql tables. Each table has a FOREIGN_KEY constant that define the field name used to join two tables.
Only direct relations are supported, (one-to-many and many-to-one) but not "many-to-many". Example:

```php
$Post = Post::selectBy(4);

$User = Users::create([
	'name' => 'Fred'
]);

$Post->set($User); //This change the field "users_id" of the post with the id of the user

$Post->save();
```

Load the related data of a row:

```php
$Post = Post::selectBy(4);

$Post->comments; //Load the comments
$Post->users; //Load the user
```


Lazy properties
---------------

With the magic method "__get()", you can define methods starting by "get" to return properties in lazy mode. Useful to refine the relations:

```php
class Posts extends Item {
	const TABLE = 'posts';
	const FOREIGN_KEY = 'posts_id';

	public $id;
	public $title;
	public $pubDate;
	public $latestUpdate;
	public $text;
	public $users_id;

	public function getComments () {
		return Comments::select("posts_id = :id AND active = 1", [':id' => $this->id]);
	}
}

$Post = Post::selectBy(34);

$comments = $Post->comments; //Execute getComments and return the result
```

And many more...