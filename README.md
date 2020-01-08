# SimpleCrud

[![Build Status](https://travis-ci.org/oscarotero/simple-crud.png?branch=master)](https://travis-ci.org/oscarotero/simple-crud)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/simple-crud/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/simple-crud/?branch=master)

PHP library to (Create, Read, Update, Delete) in Mysql/Sqlite databases with zero configuration and some magic.

## Naming conventions:

This library relies in some conventions to avoid configuration.

* Table names SHOULD be in [singular](http://stackoverflow.com/a/5841297) and **camelCase**
* Fields names SHOULD be in **singular** and **camelCase**
* The primary key of all tables MUST be `id`.
* Foreign keys MUST be `[tableName]_id`. For example, `post` table uses `post_id` as foreign key.
* Associative tables MUST use an underscore joining the two tables in alphabetic order. For example, the relationship between `post` and `tag` is `post_tag` but `post` and `category` is `category_post`.

## Installation

This package is installable and autoloadable via Composer as [simple-crud/simple-crud](https://packagist.org/packages/simple-crud/simple-crud).

```
$ composer require simple-crud/simple-crud
```

## Classes

SimpleCrud has the following classes:

* **Database:** Manage the database connection. Uses internally [Atlas.PDO](https://github.com/atlasphp/Atlas.PDO)
* **Query:** Creates the database queries. SimpleCrud is tested with MySQL and SQLite but due uses [Atlas.Query](https://github.com/atlasphp/Atlas.Query) internally, in theory Postgres and Microsoft SQL should be supported too.
* **Table:** Manages a database table
* **Field:** Manages a database field. Used to format and validate values
* **Row:** To store and modify a row
* **RowCollection:** Is a collection of rows

## Usage example

Let's say we have the following database scheme:

```sql
CREATE TABLE "post" (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title` TEXT,
    `category_id` INTEGER,
    `type`  TEXT,

    FOREIGN KEY(`category_id`) REFERENCES category(id)
);

CREATE TABLE `category` (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`  TEXT
);

CREATE TABLE `tag` (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`  TEXT
);

CREATE TABLE `post_tag` (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `tag_id`   INTEGER NOT NULL,
    `post_id`  INTEGER NOT NULL,

    FOREIGN KEY(`tag_id`) REFERENCES tag(id),
    FOREIGN KEY(`post_id`) REFERENCES post(id)
);
```

To start, create an instance of `SimpleCrud\Database` passing the `PDO` connection.

```php
use SimpleCrud\Database;

$pdo = new PDO($dsn, $username, $password);

$db = new Database($pdo);

//To get any table, use magic properties, they will be instantiated on demand:
$post = $db->post;
```

SimpleCrud load the database scheme and detects automatically all relationships between the tables using the naming conventions described above. For example the table "post" has a field called "category_id", so SimpleCrud knows that each post has one category.

**Note:** In production environment, you may want to cache the scheme in order to avoid execute these queries and improve the performance. You can do it in this way:

```php
use SimpleCrud\Scheme\Cache;
use SimpleCrud\Scheme\Mysql;

if ($cache->has('db_scheme')) {
    $array = $cache->get('db_scheme');
    $scheme = new Cache($array);
} else {
    $scheme = new Mysql($pdo);
    $cache->save('db_scheme', $scheme->toArray());
}

$db = new Database($pdo, $scheme);
```

## Using the library

### Basic CRUD:

You can interact directly with the tables to insert/update/delete/select data:

Use `ArrayAccess` interface to access to the data using the `id`:

```php
//Get the post id = 3;
$post = $db->post[3];

//Check if a row exists
if (isset($db->post[3])) {
    echo 'exists';
}

//Delete a post
unset($db->post[3]);

//Update a post
$db->post[3] = [
    'title' => 'Hello world'
];

//Insert a new post
$db->post[] = [
    'title' => 'Hello world 2'
];

//Tables implements the Countable interface
$totalPost = count($db->post);
```

### Select by other fields

If you want to select a row by other key than `id`, just use the method `get`:

```php
$post = $db->post->get(['slug' => 'post-slug']);
```

### Select or create

Sometimes, you want to get a row or create it if it does not exist. You can do it easily with `getOrCreate` method:

```php
$post = $db->post->getOrCreate(['slug' => 'post-slug']);
```

### Rows

A `Row` object represents a database row and is used to read and modify its data:

```php
//get a row by id
$post = $db->post[34];

//Get/modify fields values
echo $post->title;

$post->title = 'New title';

//Update the row into database
$post->save();

//Remove the row in the database
$post->delete();

//Create a new row
$newPost = $db->post->create(['title' => 'The title']);

//Insert the row in the database
$newPost->save();
```

### Queries

A `Query` object represents a database query. SimpleCrud uses magic methods to create queries. For example `$db->post->select()` returns a new instance of a `Select` query in the tabe `post`. Other examples: `$db->comment->update()`, `$db->category->delete()`, etc... Each query has modifiers like `orderBy()`, `limit()`:

```php
//Create an UPDATE query with the table post
$updateQuery = $db->post->update(['title' => 'New title']);

//Add conditions, limit, etc
$updateQuery
    ->where('id = ', 23)
    ->limit(1);

//get the query as string
echo $updateQuery; //UPDATE `post` ...

//execute the query and returns a PDOStatement with the result
$PDOStatement = $updateQuery();
```

The method `get()` executes the query and returns the processed result of the query. For example, with `insert()` returns the id of the new row:

```php
//insert a new post
$id = $db->post
    ->insert([
        'title' => 'My first post',
        'text' => 'This is the text of the post'
    ])
    ->get();

//Delete a post
$db->post
    ->delete()
    ->where('id = ', 23)
    ->get();

//Count all posts
$total = $db->post
    ->selectAggregate('COUNT')
    ->get();
//note: this is the same like count($db->post)

//Sum the ids of all posts
$total = $db->post
    ->selectAggregate('SUM', 'id')
    ->get();
```

`select()->get()` returns an instance of `RowCollection` with the result:

```php
$posts = $db->post
    ->select()
    ->where('id > ', 10)
    ->orderBy('id ASC')
    ->limit(100)
    ->get();

foreach ($posts as $post) {
    echo $post->title;
}
```

If you only need the first row, use the modifier `one()`:

```php
$post = $db->post
    ->select()
    ->one()
    ->where('id = ', 23)
    ->get();

echo $post->title;
```

`select()` has some interesting modifiers like `relatedWith()` to add automatically the `WHERE` clauses needed to select data related with other row or rowCollection:

```php
//Get the post id = 23
$post = $db->post[23];

//Select the category related with this post
$category = $db->category
    ->select()
    ->relatedWith($post)
    ->one()
    ->get();
```

### Query API:

Queries use [Atlas.Query](http://atlasphp.io/cassini/query/) library to build the final queries, so you can see the documentation for all available options.

#### Select / SelectAggregate

Function | Description
---------|------------
`one` | Select 1 result.
`relatedWith(Row / RowCollection / Table $relation)` | To select rows related with other rows or tables (relation added in `WHERE`).
`joinRelation(Table $table)` | To add a related table as `LEFT JOIN`.
`getPageInfo()` | Returns the info of the pagination.
`from` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`columns` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`join` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`catJoin` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`groupBy` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`having` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`orHaving` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`orderBy` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`catHaving` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`where` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`whereSprintf` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`catWhere` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`orWhere` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`orWhereSprintf` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`whereEquals` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`limit` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`offset` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`distinct` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`forUpdate` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`setFlag` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)
`bindValue` | [Atlas.Query Select()](http://atlasphp.io/cassini/query/select.html)

#### Update

Function | Description
---------|------------
`relatedWith(Row / RowCollection / Table $relation)` | To update rows related with other rows or tables (relation added in `WHERE`).
`set` | [Atlas.Query Update()](http://atlasphp.io/cassini/query/update.html)
`setFlag` | [Atlas.Query Update()](http://atlasphp.io/cassini/query/update.html)
`where` | [Atlas.Query Update()](http://atlasphp.io/cassini/query/update.html)
`orWhere` | [Atlas.Query Update()](http://atlasphp.io/cassini/query/update.html)
`catWhere` | [Atlas.Query Update()](http://atlasphp.io/cassini/query/update.html)
`orderBy` | [Atlas.Query Update()](http://atlasphp.io/cassini/query/update.html)
`limit` | [Atlas.Query Update()](http://atlasphp.io/cassini/query/update.html)
`offset` | [Atlas.Query Update()](http://atlasphp.io/cassini/query/update.html)

#### Insert

Function | Description
---------|------------
`orIgnore()` | To ignore silently the insertion on duplicated keys, instead throw an exception.
`set` | [Atlas.Query Insert()](http://atlasphp.io/cassini/query/insert.html)
`setFlag` | [Atlas.Query Insert()](http://atlasphp.io/cassini/query/insert.html)

#### Delete

Function | Description
---------|------------
`relatedWith(Row / RowCollection / Table $relation)` | To delete rows related with other rows or tables (relation added in `WHERE`).
`setFlag` | [Atlas.Query Delete()](http://atlasphp.io/cassini/query/delete.html)
`where` | [Atlas.Query Delete()](http://atlasphp.io/cassini/query/delete.html)
`orWhere` | [Atlas.Query Delete()](http://atlasphp.io/cassini/query/delete.html)
`catWhere` | [Atlas.Query Delete()](http://atlasphp.io/cassini/query/delete.html)
`orderBy` | [Atlas.Query Delete()](http://atlasphp.io/cassini/query/delete.html)
`limit` | [Atlas.Query Delete()](http://atlasphp.io/cassini/query/delete.html)
`offset` | [Atlas.Query Delete()](http://atlasphp.io/cassini/query/delete.html)

### Lazy loads

Both `Row` and `RowCollection` can load automatically other related rows. Just use a property named as related table. For example:

```php
//Get the category id=34
$category = $db->category[34];

//Load the posts of this category
$posts = $category->post;

//This is equivalent to:
$posts = $db->post
    ->select()
    ->relatedWith($category)
    ->get();

//But the result is cached so the database query is executed only the first time
$posts = $category->post;
```

This allows make things like this:

```php
$titles = $db->post[34]->tag->post->title;

//Get the post id=34
//Get the tags of the post
//Then the posts related with these tags
//And finally, the titles of all these posts
```

Use magic methods to get a `Select` query returning related rows:

```php
$category = $db->category[34];

//Magic property: Returns all posts of this category:
$posts = $category->post;

//Magic method: Returns the query instead the result
$posts = $category->post()
    ->where('pubdate > ', date('Y-m-d'))
    ->limit(10)
    ->get();
```

### Solving the n+1 problem

The [n+1 problem](http://stackoverflow.com/questions/97197/what-is-the-n1-selects-issue) can be solved in the following way:

```php
//Get some posts
$posts = $db->post
    ->select()
    ->get();

//preload all categories
$posts->category;

//now you can iterate with the posts
foreach ($posts as $post) {
    echo $post->category;
}
```

You can perform the select by yourself to include modifiers:

```php
//Get some posts
$posts = $db->post
    ->select()
    ->get();

//Select the categories but ordered alphabetically descendent
$categories = $posts->category()
    ->orderBy('name DESC')
    ->get();

//Save the result in the cache and link the categories with each post
$posts->link($categories);

//now you can iterate with the posts
foreach ($posts as $post) {
    echo $post->category;
}
```

For many-to-many relations, you need to do one more step:

```php
//Get some posts
$posts = $db->post
    ->select()
    ->get();

//Select the post_tag relations
$tagRelations = $posts->post_tag()->get();

//And now the tags of these relations
$tags = $tagRelations->tag()
    ->orderBy('name DESC')
    ->get();

//Link the tags with posts using the relations
$posts->link($tags, $tagRelations);

//now you can iterate with the posts
foreach ($posts as $post) {
    echo $post->tag;
}
```


### Relate and unrelate data

To save related rows in the database, you need to do this:

```php
//Get a comment
$comment = $db->comment[5];

//Get a post
$post = $db->post[34];

//Relate
$post->relate($comment);

//Unrelate
$post->unrelate($comment);

//Unrelate all comments of the post
$post->unrelateAll($db->comment);
```

### Pagination

The `select` query has a special modifier to paginate the results:

```php
$query = $db->post->select()
    ->page(1)
    ->perPage(50);

$posts = $query->get();

//To get the page info:
$pagination = $query->getPageInfo();

echo $pagination['totalRows']; //125
echo $pagination['totalPages']; //3
echo $pagination['currentPage']; //1
echo $pagination['previousPage']; //NULL
echo $pagination['nextPage']; //2
```

### Events

SimpleCrud uses [PSR-14 Event Dispatcher](https://www.php-fig.org/psr/psr-14/) to dispatch events. The events are attached to tables allowing to validate data, modify queries, etc.

```php
use SimpleCrud\Events\BeforeSaveRow;
use SimpleCrud\Events\CreateSelectQuery;

//Get the event dispatcher
$dispatcher = $db->post->getEventDispatcher();

//Assign the BeforeSaveRow event listener
$dispatcher->listen(BeforeSaveRow::class, function (BeforeSaveRow $event) {
    $row = $event->getRow();

    if (!$row->createdAt) {
        $row->createdAt = new Datetime();
    }
});

//Assign a CreateSelectQuery
$dispatcher->listen(CreateSelectQuery::class, function (CreateSelectQuery $event) {
    $query = $event->getQuery();

    //Add automatically a where clause in all selects
    $query->where('active = true');
});

//Create a new post
$post = $db->post->create(['title' => 'Hello world']);

//Save the post, so BeforeSaveRow event is triggered
$post->save();

$post->createdAt; //This field was filled and saved

//Select a post, so CreateSelectQuery is triggered and only active posts are selected
$posts = $db->post->select()->get();
```

You can provide your own event dispatcher:

```php
$myDispatcher = new Psr14EventDispatcher();

$db->post->setEventDispatcher($myDispatcher);
```

The available Events are:

* `SimpleCrud\Events\BeforeSaveRow`: Executed before save a row using `$row->save()`.
* `SimpleCrud\Events\BeforeCreateRow`: Executed before create a new row with `$table->create()`.
* `SimpleCrud\Events\CreateDeleteQuery`: Executed on create a DELETE query with `$table->delete()`.
* `SimpleCrud\Events\CreateInsertQuery`: Executed on create a INSERT query with `$table->insert()`.
* `SimpleCrud\Events\CreateSelectQuery`: Executed on create a SELECT query with `$table->select()`.
* `SimpleCrud\Events\CreateUpdateQuery`: Executed on create a UPDATE query with `$table->update()`.

### Fields

The purpose of the `SimpleCrud\Fields` classes is to convert the data from/to the database for its usage. For example, in Mysql the format used to store datetime values is "Y-m-d H:i:s", so the class `SimpleCrud\Fields\Datetime` converts any string or `Datetime` instance to this format, and when you select this value, you get a Datetime instance. The available fields are:

* Boolean: To manage boolean values
* Date: To manage date values. Converts the database values to a `Datetime`
* Datetime: To manage datetime values. Converts the database values to a `Datetime`
* Decimal: Converts values to float numbers or NULL
* Field: It's the default field and doesn't transform the value
* Integer: Converts values to integers or NULL
* Json: To store json structures.
* Serializable: To store arrays or any other serializable data structure.
* Set: Manages multiple values. For example: ['red', 'blue', 'green'] will be stored as "red,blue,green" in database.
* Point: Manages geometry points [more info](https://dev.mysql.com/doc/refman/5.7/en/gis-class-point.html)
* Other advanced fields can be found here: https://github.com/oscarotero/simple-crud-extra-fields

The Field classes are asigned automatically according with the field type in the database. There are also "special names" that have specific types asigned:

* Integer format will be asigned to any field named `id` or ending by `_id`.
* Datetime format will be asigned to any field named `pubdate` or ending by `At` (for example: `createdAt`, `updatedAt` etc).
* Boolean format will be asigned to any field named `active` or starting by `is` or `has` (for example: `isActived`, `hasContent`, etc)

Example:

```php
$post = $db->post->create([
    'title' => 'My post',
    'text' => 'My post text',
    'createdAt' => new Datetime('now'),
    'isActive' => true
]);

$post->save();

//Use magic properties to get the Field instance
$titleField = $db->post->title;
```

### Configuration

You may want to store some database configuration, for example the default language or base path where the assets are stored. To do that, there are the `getConfig` and `setConfig` methods:

```php
$db->setConfig('name', 'value');

echo $db->getConfig('name'); //value
```

### Localizable fields

If you need to save values in multiple languages, just have to create a field for each language using the language as suffix. For example, to save the title in english (en) and galician (gl), just create the fields `title_en` and `title_gl`.

Then, you have to configure the current language using the `SimpleCrud::ATTR_LOCALE` attribute:

```php
//Set the current language as "en"
$db->setConfig(SimpleCrud::CONFIG_LOCALE, 'en');

//Select a post
$post = $db->post[23];

//Get the title in the current language
echo $post->title; //Returns the value of title_en

//You can access to any languages using the full name:
echo $post->title_en;
echo $post->title_gl;

//And assign a diferent value to the current language
$post->title = 'New title in english';
```

## Debugging

`SimpleCrud` use internally [Atlas.PDO](http://atlasphp.io/cassini/pdo/) to manage the connection and perform the queries in the database. You can see the documentation for more details.

```php
$db->getConnection()->logQueries(true);

//-- Run queries --//

$queries = $db->getConnection()->getQueries();
```

## Customization

You can use your own custom classes for tables, rows and row collections:

### Custom Tables

Use `setTableClasses` to assign custom classes to table:

```php
$db = new SimpleCrud\Database($pdo);

$db->setTableClasses([
    'post' => CustomPost::class,
    'comment' => CustomComment::class,
]);

$db->post; //Returns an instance of CustomPost
```

### FieldFactory

To create field instances, SimpleCrud use the `SimpleCrud\Field\FieldFactory` factory class that you can customize or even replace with your own factory:

```php
use SimpleCrud\Fields\FieldFactory;
use SimpleCrud\Fields\Boolean;

$db = new SimpleCrud\Database($pdo);

//Create a factory for your custom field
$factory = new FieldFactory(
    Year::class,          //Your custom field class name
    ['integer'],          //All fields of type integer will use this class
    ['year', '/$year/'],  //All fields named "year" or matching this regex will use this class
    ['min' => 2000],      //Default config
);

$db->setFieldFactory($factory);

//Modify a existing field
$db->getFieldFactory(Boolean::class)->addNames('enabled');

//Use it:
$db->post->fields['year']; //returns an instance of Year
$db->post->fields['enabled']; //returns an instance of SimpleCrud\Fields\Boolean
```

## Creating your Rows and RowCollections

To define the Rows and RowCollections classes used in a specific table, first create a custom table and use `ROW_CLASS` and `ROWCOLLECTION_CLASS` protected constants to set the class.

```php
namespace MyModels;

use SimpleCrud\Table;

class Post extends Table
{
    protected const ROW_CLASS = PostRow::class;
    protected const ROWCOLLECTION_CLASS = PostRowCollection::class;

    protected function init()
    {
        //Insert code to be executed after the instantion
    }

    public function selectLatest()
    {
        return $this->select()
            ->orderBy('createdAt DESC')
            ->limit(10);
    }
}
```

Now configure the database to use this class for the table `post`:

```php
$db = new SimpleCrud\Database($pdo);
$db->setTableClasses([
    'post' => MyModels\Post::class,
]);


$latests = $db->post->selectLatest()->get(); //Returns an instance of MyModels\PostRowCollection

foreach ($latests as $post) {
    //Instances of MyModels\PostRow
}
```
