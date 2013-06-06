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


API
===

Item::selectBy
--------------

Select an element by id or another index key:

Select by id:

```php $post = Posts::selectBy(23); ```

Select various ids:

```php $posts = Posts::selectBy([45, 46, 47, 48]); ```

Select by another index key name (for example, "slug"):

```php $posts = Posts::selectBy('my-first-post', 'slug'); ```

Select items related with other items:

```php $comments = Comments::selectBy(Posts::selectBy(34)); ```


Item::selectOne
---------------

Select the first item:

Add a "where" condition:

```php $post = Posts::selectOne('id < 34'); ```

Add a "where" condition and marks:

```php $post = Posts::selectOne('id < :id', [':id' => 34]); ```

Add a "where" condition, marks and "order by":

```php $post = Posts::selectOne('id < :id', [':id' => 34], 'id DESC'); ```


Item::selectAll
---------------

It's the same than Item::selectOne but returns all found rows, instead of just the first one:

Add a "where" condition:

```php $posts = Posts::selectAll('id < 34'); ```

Add a "where" condition and marks:

```php $posts = Posts::selectAll('id < :id', [':id' => 34]); ```

Add a "where" condition, marks and "order by":

```php $posts = Posts::selectAll('id < :id', [':id' => 34], 'id DESC'); ```

Add a "where" condition, marks, "order by" and limit:

```php $posts = Posts::selectAll('id < :id', [':id' => 34], 'id DESC', 3); ```


Item::fetch
-----------

Returns the first item found. The main difference with Item::selectOne is that you must write the full SQL query. It's a good solution for complicated queries:

Passing a query:

```php $post = Posts::fetch('SELECT * FROM posts WHERE id = 34 LIMIT 1'); ```

Passing a query and the marks:

```php $post = Posts::fetch('SELECT * FROM posts WHERE id = :id LIMIT 1', [':id' => 34]); ```


Item::fetchAll
--------------

It's like Item::fetch but returns the all items found.

Passing a query:

```php $posts = Posts::fetchAll('SELECT * FROM posts WHERE id > 34 LIMIT 10,20'); ```

Passing a query and the marks:

```php $posts = Posts::fetchAll('SELECT * FROM posts WHERE id > :id LIMIT 10,20', [':id' => 34]); ```


Item::set
---------

Set new values to one or various fields. If the field does not exists in database throws an exception:

Edit a field:

```php
$post->set('title', 'New title name');

//This is the samen than:
$post->title = 'New title name';
```

Edit various fields:

```php
$post->set([
	'title' => 'New title name',
	'body' => 'New body content'
]);
```

Item::get
---------

Return one or all values of a row. Only returns fields that exist in the database:

Get one value

```php
$title = $post->get('title');

//This is the samen than:
$title = $post->title;
```

Get all values (return an array)

```php
$data = $post->get();

$title = $data['title'];
```

Item::save
----------

Insert or update the value in database (if the field "id" has any value, update, if not insert)

```php
$post->title = 'New title';

$post->save();
```

Item::delete
------------

Deletes this row from the database:

```php
$post = Posts::selectBy(34);

$post->delete();
```

Item::create
------------

Creates a new instance (without save it in the database):

Create an empty item:

```php
$post = Posts::create();
```

Create an item with values:

```php
$post = Posts::create([
	'title' => 'New title',
	'body' => 'Post body'
]);

//Insert the new item in the database
$post->save();
```