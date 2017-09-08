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

## Components

SimpleCrud has the following classes:

* **SimpleCrud:** Manage the database connection.
* **Query:** Creates the database queries. Currently there are adapters for mysql and sqlite
* **Table:** Manages a database table
* **Row:** Stores/modifies a row
* **RowCollection:** Is a collection of rows
* **Field:** Used to modify the values from/to the database according to its format

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

To start, create an instance of `SimpleCrud\SimpleCrud` passing the `PDO` connection.

```php
use SimpleCrud\SimpleCrud;

$pdo = new PDO($dsn, $username, $password);

$db = new SimpleCrud($pdo);

//To get any table, use magic properties, they will be instantiated on demand:
$post = $db->post;
```

SimpleCrud load the database scheme and detects automatically all relationships between the tables using the naming conventions described above. For example the table "post" has a field called "category_id", so SimpleCrud knows that each post has one category.

**Note:** In production environment, you may want to cache the scheme in order to improve the performance. You can do it in this way:

```php
if (!$cache->has('db_scheme')) {
    $cache->save($db->getScheme());
} else {
    $db->setScheme($cache->get('db_scheme'));
}
```

## Using the library

### Basic CRUD:

You can work directly with the tables to insert/update/delete/select data:

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
```

### Rows

A `Row` object represents a database row and it is used to read and modify the data:

```php
//get a row
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

A `Query` object represents a database query. Use magic methods to create query instances. For example `$db->post->select()` returns a new instance of a `Select` query. Other examples: `$db->comment->update()`, `$db->category->count()`, etc... Each query has modifiers like `orderBy()`, `limit()`:

```php
//Create an UPDATE query with the table post
$updateQuery = $db->post->update();

//Add data, conditions, limit, etc
$updateQuery
    ->data(['title' => 'New title'])
    ->where('id = :id', [':id' => 23])
    ->limit(1);

//get the query as string
echo $updateQuery; //UPDATE `post` ...

//execute the query and returns a PDOStatement with the result
$statement = $updateQuery();
```

The method `run()` executes the query but instead returns the `PDOStatement`, it returns the processed result of the query. For example, with `count()` returns an integer with the number of rows found, and with `insert()` returns the id of the new row:

```php
//insert a new post
$id = $db->post
    ->insert()
    ->data([
        'title' => 'My first post',
        'text' => 'This is the text of the post'
    ])
    ->run();

//Delete a post
$db->post
    ->delete()
    ->byId(23) //shortcut of where('id = :id', [':id' => 23])
    ->run();

//Count all posts
$total = $db->post
    ->count()
    ->run();

//Sum the ids of all posts
$total = $db->post
    ->sum()
    ->field('id')
    ->run();
```

`select()` returns an instance of `RowCollection` with the result:

```php
$posts = $db->post
    ->select()
    ->where('id > :id', [':id' => 10])
    ->orderBy('id ASC')
    ->limit(100)
    ->run();

foreach ($posts as $post) {
    echo $post->title;
}
```

If you only need the first row, use the modifier `one()`:

```php
$post = $db->post
    ->select()
    ->one()
    ->by('id', 23)
    ->run();

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
    ->run();
```

### List of available queries with its modifiers:

Name | Options
---- | -------
`select` | `one()`, `all()`, `leftJoin($table, $on, $marks)`, `from($table)`, `field($field)`, `relatedWith($row)`,  `marks($marks)`, `where($where, $marks)`, `orWhere($where, $marks)`, `by($field, $value)`, `byId($id)`, `limit($limit)`, `offset($offset)`, `orderBy($row, $direction)`, `page($page, $limit)`
`count` | `from($table)`, `field($field)`, `relatedWith($row)`,  `marks($marks)`, `where($where, $marks)`, `orWhere($where, $marks)`, `by($field, $value)`, `byId($id)`, `limit($limit)`, `offset($offset)`
`delete` | `marks($marks)`, `where($where, $marks)`, `orWhere($where, $marks)`, `by($field, $value)`, `byId($id)`, `limit($limit)`, `offset($offset)`
`insert` | `data($data)`, `duplications($handle)`
`sum` | `from($table)`, `field($field)`, `relatedWith($row)`,  `marks($marks)`, `where($where, $marks)`, `orWhere($where, $marks)`, `by($field, $value)`, `byId($id)`, `limit($limit)`, `offset($offset)`
`update` | `data($data)`, `marks($marks)`, `where($where, $marks)`, `orWhere($where, $marks)`, `by($field, $value)`, `byId($id)`, `limit($limit)`, `offset($offset)`


### Lazy loads

Both `Row` and `RowCollection` can load automatically other related data. Just use a property named like a related table. For example:

```php
//Get the category id=34
$category = $db->category[34];

//Load the posts of this category
$posts = $category->post;

//This is equivalent to:
$posts = $db->post
    ->select()
    ->relatedWith($category)
    ->run();
```

This allows make things like this:

```php
$titles = $db->post[34]->tag->post->title;

//Get the post id=34
//Get the tags of the post
//Then the posts related with these tags
//And finally, the titles of all these posts
```

You may want a filtered result of the related rows instead getting all of them. To do this, just use a method with the same name of the related table and you get the of the `Select` query that you can modify:

```php
$category = $db->category[34];

//Magic property: Returns all posts of this category:
$posts = $category->post;

//Magic method: Returns the query before run it
$posts = $category->post()
    ->where('pubdate > :date', [':date' => date('Y-m-d')])
    ->limit(10)
    ->run();
```

### Solving the n+1 problem

The [n+1 problem](http://stackoverflow.com/questions/97197/what-is-the-n1-selects-issue) can be solved in the following way:

```php
//Get some posts
$posts = $db->post
    ->select()
    ->run();

//preload all categories
$posts->category;

//now you can iterate with the posts
foreach ($posts as $post) {
    echo $post->category;
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

### Custom methods

Sometimes, it's usefult to have more methods in the rows, for example to get the value in a specific format, or to make some calculations. So, you can register new methods with `setRowMethod()` and `setRowCollectionMethod()`:

```php
//Register a method to the post rows
$db->post->setRowMethod('getUppercaseTitle', function () {
    return strtoupper($this->title);
});

echo $db->post[1]->getUppercaseTitle(); //FIRST POST TITLE
```

Note that the custom methods must be instances of `Closure` and the `$this` variable is the current row.

### Default queries modifiers

Let's say we want to select always the posts with the condition `isActived = 1`. To avoid the need to add this "where" modifier again and again, you may want to define it as default, so it's applied always:

```php
$db->post->addQueryModifier('select', function ($query) {
    $query->where('isActived = 1');
});

$post = $db->post[34]; //Returns the post 34 only if it's actived.
```

You can define default modifiers for all queries: not only select, but also update, delete, etc.


### Pagination

The `select` query has a special modifier to paginate the results:

```php
$posts = $db->post->select()
    ->page(1)
    ->limit(50)
    ->run();

//You can set the limit as second argument of page:
$posts = $db->post->select()
    ->page(1, 50)
    ->run();

//On paginate the results, you have three new methods in the result:
$posts->getPage(); //1
$posts->getPrevPage(); //NULL
$posts->getNextPage(); //2
```

**Note:** If the result length is lower than the max limit elements per page, it's assumed that there's no more pages, so `getNextPage()` returns `NULL`.

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

### Attributes

You may want to store some database attributes, for example a language configuration, the base path where the assets are stored, etc. To do that, there are the `getAttribute` and `setAttribute` methods:

```php
//Save an attribute, for example, the uploads path:
$db->setAttribute('foo', 'bar');

//Get the attribute:
echo $db->getAttribute('foo'); //bar

//You can access also to PDO attributes, using constants:
echo $db->getAttribute(PDO::ATTR_DRIVER_NAME); //sqlite
```

### Localizable fields

If you need to save values in multiple languages, just have to create a field for each language using the language as suffix. For example, to save the title in english (en) and galician (gl), just create the fields `title_en` and `title_gl`.

Then, you have to configure the current language using the `SimpleCrud::ATTR_LOCALE` attribute:

```php
//Set the current language as "en"
$db->setAttribute(SimpleCrud::ATTR_LOCALE, 'en');

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

The `SimpleCrud` instance provides the `onExecute` method allowing to register a callback that is runned by each query executed. This allows to inspect and debug what simple-crud does in the database:

```php
$db->onExecute(function ($pdo, $statement, $marks) {
    $message = [
        'query' => $statement->queryString,
        'data' => $marks
    ];

    Logger::log($message);
});
```

## Customization

SimpleCrud uses factory classes to create instances of tables, queries and fields. You can configure or create your own factories to customize how these instances are created.

### TableFactory

This class creates the instances of all tables. If it's not provided, by default uses the `SimpleCrud\TableFactory` but you can create your own factory implementing the `SimpleCrud\TableFactoryInterface`. The default TableFactory, can be configured using the following methods:

* `addNamespace` Useful if you want to create custom table classes. For example, if the namespace is `App\MyModels` and you load the table `post`, the TableFactory will check whether the class `App\MyModels\Post` exists and use it instead the default.
* `setAutocreate` Set false to NOT create instances of tables using the default class.


```php
//Create the simplecrud instance
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the table factory
$tableFactory = $db->getTableFactory();

//Add a namespace to locate custom tables:
$tableFactory->addNamespace('App\\MyModels\\');

$db->post; //Returns an instance of App\MyModels\Post
```

### QueryFactory

The query factory is the responsive to instantiate all query classes of the table. By default uses the `SimpleCrud\QueryFactory` class but you can provide your own factory extending the `SimpleCrud\QueryFactoryInterface`. The default factory has the following options:

* `addNamespace` Add more namespaces where find more query classes.

Example:

```php
//Create the simplecrud instance
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the query factory
$queryFactory = $db->getQueryFactory();

//Add a namespace with my custom query classes, with more options, etc
$queryFactory->addNamespace('App\\Models\\Queries\\');

//Use the queries:

$db->posts->customSelect()->run(); //Returns and execute an instance of App\Models\Queries\CustomSelect
```

### FieldFactory

This factory creates intances of the fields used by the tables to convert the values. By default uses `SimpleCrud\FieldFactory` but you can create your own factory extending the `SimpleCrud\FieldFactoryInstance`. The default FieldFactory has the following options:

* `addNamespace` Add more namespaces where find more field classes.
* `mapNames` To asign predefined types to some names names.
* `mapRegex` To asign predefined types to some names names using a regular expression.

Example:

```php
//Create the simplecrud instance
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the fieldFactory
$fieldFactory = $db->getFieldFactory();

//Add a namespace with my custom field classes
$fieldFactory->addNamespace('App\\Models\\Fields\\');

//By default, all fields called "year" will be integer
$fieldFactory->mapNames([
    'year' => 'Integer'
]);

//And assign the boolean type to all fields begining with "in" (for example "inHome")
$fieldFactory->mapRegex([
    '/^in[A-Z]/' => 'Boolean'
]);

//Use it:
$db->post->fields['year']; //returns an instance of App\Models\Fields\Integer
$db->post->fields['inHome']; //returns an instance of App\Models\Fields\Boolean
```

## Creating your own tables

The default behaviour of simpleCrud is fine but you may want to extend the tables with your own methods, validate data, etc. So you need to create classes for your tables. The table classes must extend the `SimpleCrud\Table` class and be named like the database table (with uppercase first letter). For example, for a table named `post` you need a class named `Post`. In the Table class you can configure the types of the fields and add your own methods:

```php
namespace MyModels;

use SimpleCrud\Table;

class Post extends Table
{
    public function selectLatest()
    {
        return $this->select()
            ->orderBy('createdAt DESC')
            ->limit(10);
    }
}
```

Now if you configure the TableFactory to look into `MyModels` namespace, it will use this class when you need `$db->post` table:

```php
$db = new SimpleCrud\SimpleCrud($pdo);
$db->getTableFactory()->addNamespace('MyModels\\');


$latests = $db->post->selectLatest()->run();
```

### Data validation

Each table has two methods to convert/validate data before push to database and after pull from it. You can overwrite this methods to customize its behaviour:

```php
namespace MyModels;

use SimpleCrud\Table;

class Post extends Table
{
    public function dataToDatabase (array $data, $new)
    {
        $data['updatedAt'] = new \Datetime('now');

        if ($new) { //it's an insert
            $data['createdAt'] = $data['updatedAt'];
        }

        return $data;
    }

    public function dataFromDatabase (array $data)
    {
        //convert the date to format "2 days ago"
        $data['updatedAt'] = convertData($data['updatedAt']);

        return $data;
    }
}
```

### Customize Row and RowCollection

The Table class has the method `init` that you can use to initialize things. It's called at the end of the `__construct`. This allows to configure the table after the instantiation, for example to use custom `Row` or `RowCollection` classes, extend them with custom methods or configure the table fields:

```php
namespace MyModels;

use SimpleCrud\Table;

class Post extends Table
{
    public function init()
    {
        //Use a custom RowCollection class:
        $this->setRowCollection(new MyCustomRowCollection($this));

        //or configure custom methods:
        $this->setRowMethod('getTitleText', function () {
            return strip_tags($this->title);
        });

        //or configure some field
        $this->fields['jsonData']->setConfig(['assoc' => false]);
    }
}
```

### Create your own custom fields

You can create your own fields types or overwrite the existing ones registering the namespaces with your custom fields in the FieldFactory.

Let's see an example:

```php
namespace MyModels\Fields;

use SimpleCrud\FieldInterface;

/**
 * Format to store ips as numeric values
 */
class Ip extends SimpleCrud\Fields\Field
{
    public function dataToDatabase($data)
    {
        return ip2long($data);
    }

    public function dataFromDatabase($data)
    {
        return long2ip($data);
    }
}
```

Now, to use it:

```php
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the field factory
$fieldFactory = $db->getFieldFactory();

//Add the namespace of my custom fields
$fieldFactory->addNamespace('MyModels\\Fields\\');

//All fields named "ip" use the class "Ip"
$fieldFactory->mapNames([
    'ip' => 'Ip'
]);

//Use in the ip fields
$db->session->insert()
    ->data(['ip' => '0.0.0.0'])
    ->run();
```

### Create your own custom queries

Let's see an example of how to extend the Select query with custom methods:

```php
namespace MyModels\Queries;

use SimpleCrud\Queries\Mysql\Select as BaseSelect;

class Select extends BaseSelect
{
    public function actived()
    {
        return $this->where('active = 1');
    }

    public function olderThan(\Datetime $date)
    {
        return $this->where('createdAt < :date', [':date' => $date->format('Y-m-d H:i:s')]);
    }
}
```

Now to use it:

```php
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the query factory
$queryFactory = $db->getQueryFactory();

//Add the namespace of my custom queries
$queryFactory->addNamespace('MyModels\\Queries\\');

//use in your select queries
$posts = $db->post->select()
    ->actived()
    ->olderThan(new Datetime('now'))
    ->run();
```
