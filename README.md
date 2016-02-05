# SimpleCrud

> ## New v6.x version with some breaking changes!!

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

## Components

SimpleCrud has the following classes:

* **SimpleCrud:** Manage the database connection, execute the queries and create all entities. 
* **Query:** Creates the database queries. Currently there are adapters for mysql and sqlite
* **Entity:** Manages a database table
* **Row:** Stores/modifies a row
* **RowCollection:** Is a collection of rows
* **Fields:** Used to modify the values from/to the database according with its format

## Usage example

Let's say we have the following database scheme:

```sqlite
CREATE TABLE "post" (
    `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title` TEXT,
    `category_id` INTEGER,
    `pubdate`   TEXT,
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

$db = new SimpleCrud($pdo);

//Get any entity, using magic properties, they will be created on demand:
$post = $db->post;
```

SimpleCrud detects automatically all relationships between the tables using the naming conventions described above. For example the table "post" has a field called "category_id", so SimpleCrud knows that each post has one category.


### Using the library

#### Basic CRUD:

You can work directly with the entities to insert/update/delete/select data:

Use arrayAccess interface for access to the data using the `id`:

```php
//Get the post id = 3;
$post = $db->posts[3];

//Check if a row exists
if (isset($db->posts[3])) {
    echo 'exists';
}

//Delete a post
unset($db->posts[3]);

//Create or update posts
$db->posts[3] = [
    'title' => 'Hello world'
];

//Insert a new post
$db->posts[] = [
    'title' => 'Hello world 2'
];
```

#### Database queries:

Use magic methods to create database queries. For example `select()`, `update()`, `count()`, `delete()`, etc... Each query has modifiers like `orderBy()`, `limit()`, etc, and the magic methods `__toString()` (to return the query as string) and `__invoke()` to execute the query and return a `PDOStatement` instance with the result:

```php
//create the query
$updateQuery = $db->post->update();

//apply some modifiers
$updateQuery
    ->data(['title' => 'New title'])
    ->where('id = :id', [':id' => 23])
    ->limit(1);

//get the query as string
echo $updateQuery; //UPDATE `posts` ...

//execute the query
$updateQuery();
```

If you don't need the PDOStatement (the most cases) you can use the method `run()`:

```php
//insert a new post
$db->post->insert()
    ->data([
        'title' => 'My first post',
        'text' => 'This is the text of the post'
    ])
    ->run();

//Delete a post
$db->post->delete()
    ->byId(23) //shortcut of where('id = :id', [':id' => 23])
    ->run();
```

The method `run()` returns also the data from queries like `count()` or `sum()`:

```php
$count = $db->post->count();
$statement = $count();  //execute the query and returns the statement
$total = $count->run(); //execute the query and returns the result as a integer
```

Use `select()` to get data from the database. `run()` returns an instance of `RowCollection` with the result:

```php
$posts = $db->post->select()
    ->where('id > :id', [':id' => 10])
    ->orderBy('id ASC')
    ->limit(100)
    ->run();

foreach ($posts as $post) {
    echo $post->title;
}
```

To select only one row, use the modifier `->one()`:

```php
$post = $db->post->select()
    ->by('id', 23)
    ->one()
    ->run();

echo $post->title;
```

The method `relatedWith()` allows to select related data easily:

```php
//Get the post id = 23
$post = $db->post[23];

//Select the category related with this posts
$category = $db->category->select()
    ->relatedWith($post)
    ->one()
    ->run();
```

#### Working with rows

The `Row` class is used to read and modify the data of a row:

```php
//get a row
$post = $db->post[34];

//Get/set the post title
echo $post->title;

$post->title = 'New title';

//Update the data into database
$post->save();

//Remove the row in the database
$post->delete();

//Create a new row (but it's not inserted in the database yet)
$newPost = $db->post->create(['title' => 'The title']);

//Edit the values using an array
$newPost->set([
    'title' => 'New title',
    'description' => 'Another description'
]);

$newPost->save(); //Insert the post in the database.
```

#### Working with collections

The `RowCollection` class is like an array of `Row` using the id as the key.

```php
$allPosts = $db->post->select()->run();

foreach ($allPosts as $post) {
    echo $post->title;
}

//Get an array with the values of a column:
$allPostsTitles = $allPosts->title;

foreach ($allPostsTitles as $title) {
    echo $title;
}
```

#### Fields classes

The purpose of the `SimpleCrud\Fields` classes is to convert the data between the database and the entity. For example, in Mysql the format used to store datetime values is "Y-m-d H:i:s", so the class `SimpleCrud\Fields\Datetime` converts any string or `Datetime` instance to this format, and when you select this value, you get a Datetime instance. The available fields are:

* Boolean: To manage boolean values
* Date: To manage date values
* Datetime: To manage datetime values
* Decimal: Converts values to float numbers or NULL
* Field: It's the default field and doesn't transform the value
* File: Used to upload a file and save the file path
* Integer: Converts values to integers or NULL
* Json: To store json structures.
* Serializable: To store arrays or any other serializable data structure as strings.
* Set: Manages multiple values. For example: ['red', 'blue', 'green'] will be stored as "red,blue,green" in database.

The Field classes are asigned automatically according with the field type in the database. There are also "special names" that have specific types asigned:

* Integer format will be asigned to any field named `id` or ending by `_id`.
* Datetime format will be asigned to any field named `pubdate` or ending by `At` (for example: `createdAt`, `updatedAt` etc).
* Boolean format will be asigned to any field named `active` or starting by `is`, `in` or `has` (for example: `isActived`, `inHome`, `hasContent`, etc)

Example:

```php
$post = $db->post->create([
    'title' => 'My post',
    'text' => 'My post text',
    'createdAt' => new Datetime('now'),
    'isActive' => true
]);

$post->save();
```

### Lazy loads

Both `Row` and `RowCollection` can load automatically other related data. Just use a property with the same name than a related entity. For example:

```php
//Get the category id=34
$category = $db->category[34];

//Load the posts of this category
$posts = $category->post;

//This is equivalent to:
$posts = $db->post->select()
    ->relatedWith($category)
    ->run();
```

This allows make awesome (and dangerous :D) things like this:

```php
$titles = $db->post[34]->tag->post->title;

//Get the post id=34
//Get the tags of the post
//Then the posts related with these tags
//And finally, the titles of all these posts
```

You may want to modify the query before get the result. Use the magic method instead the property:

```php
$category = $db->category[34];

$posts = $category->post()
    ->where('pubdate > :date', date('Y-m-d'))
    ->run();
```

### Solving the n+1 problem

The [n+1 problem](http://stackoverflow.com/questions/97197/what-is-the-n1-selects-issue) can be solved in the following way:

```php
$posts = $db->post->select()->run();

//preload all categories
$posts->category;

//now you can iterate with the posts
foreach ($posts as $post) {
    echo $post->category;
}
```

## Customization

SimpleCrud uses factory classes to create instances of entities, queries and fields. You can configure or create your own factories to customize how these instances are created. 

### EntityFactory

This class creates the instances of all entities. If it's not provided, by default uses the `SimpleCrud\EntityFactory` but you can create your own factory implementing the `SimpleCrud\EntityFactoryInterface`. The default EntityFactory, can be configured using the following methods:

* `addNamespace` Useful if you want to create custom entity classes. For example, if the namespace is `App\MyModels` and you load the entity `post`, the EntityFactory will check whether the class `App\MyModels\Post` exists and use it instead the default.
* `setAutocreate` Set false to NOT create instances of entities using the default class.
* `getFieldFactory` Returns the FieldFactory.
* `setFieldFactory` To set a custom factory to create Field instances.
* `getQueryFactory` Returns the QueryFactory.
* `setQueryFactory` To set a custom factory to create Query instances.

```php
//Create the simplecrud instance
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the entity factory
$entityFactory = $db->getEntityFactory();

//Add a namespace to locate custom entities:
$entityFactory->addNamespace('App\\MyModels\\');

$db->post; //Returns an instance of App\MyModels\Post
```

### QueryFactory

The query factory is the responsive to instantiate all query classes of the entity. By default uses the `SimpleCrud\QueryFactory` class but you can provide your own factory extending the `SimpleCrud\QueryFactoryInterface`. The default factory has the following options:

* `addNamespace` Add more namespaces where find more query classes.

Example:

```php
//Create the simplecrud instance
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the entity factory
$entityFactory = $db->getEntityFactory();

//Get the queryFactory used by the entityFactory
$queryFactory = $entityFactory->getQueryFactory();

//Add a namespace with my custom query classes, with more options, etc
$queryFactory->addNamespace('App\\Models\\Queries\\');

//Use the queries:

$db->posts->customSelect()->run(); //Returns and execute an instance of App\Models\Queries\CustomSelect
```

### FieldFactory

This factory creates intances of the fields used by the entities to convert the values. By default uses `SimpleCrud\FieldFactory` but you can create your own factory extending the `SimpleCrud\FieldFactoryInstance`. The default FieldFactory has the following options:

* `addNamespace` Add more namespaces where find more field classes.
* `addSmartName` Add more smart names to asign automatically types to specific field names.

Example:

```php
//Create the simplecrud instance
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the entity factory
$entityFactory = $db->getEntityFactory();

//Get the fieldFactory used by the entityFactory
$fieldFactory = $entityFactory->getFieldFactory();

//Add a namespace with my custom field classes
$fieldFactory->addNamespace('App\\Models\\Fields\\');

//By default, all fields called "year" will be integer
$fieldFactory->addSmartName('year', 'Integer');

//Use it:
$db->post->fields['year']; //returns an instance of App\Models\Fields\Integer
```

## Creating your own entities

The default behaviour of simpleCrud is fine but you may want to extend the entities with your own methods, validate data, etc. So you need to create classes for your entities. The entity classes must extend the `SimpleCrud\Entity` class and be named like the database table (with uppercase first letter). For example, for a table named `post` you need a class named `Post`. In the Entity class you can configure the types of the fields and add your own methods:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public function getLatests()
    {
        return $this->select()
            ->orderBy('createdAt DESC')
            ->limit(10)
            ->run();
    }
}
```

Now if you configure the EntityFactory to look into `MyModels` namespace, it will use this class when you need `$db->post` entity:

```php
$db = new SimpleCrud\SimpleCrud($pdo);
$db->getEntityFactory()->addNamespace('MyModels\\');


$latests = $db->post->getLatest();
```

### Data validation

Each entity has two methods to convert/validate data before push to database and after pull from it. You can overwrite this methods to customize its behaviour:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
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

The Entity class has the method `init` that you can use to initialize things. It's called at the end of the `__construct`. This allows to configure the entity after the instantiation, for example to use custom `Row` or `RowCollection` classes or extend them with other methods and properties:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public function init()
    {
        //Add some methods to RowCollections
        $this->collection->registerMethod('sumIds', function ($collection) {
            return array_sum($collection->id);
        });

        //Add some properties to Rows
        $this->row->registerProperty('titleLowerCase', function ($row) {
            return strtolower($row->title);
        });
    }
}
```

Now, on use the entity:

```php
$posts = $db->post->select()->run();

//Execute the registered method in the collection
echo $posts->sumIds();

//Execute the registered property in the row
foreach ($posts as $post) {
    echo $post->titleLowerCase;
}
```

### Create your own custom fields

You can create your own fields types or overwrite the existing ones. You have to register the namespaces with your custom fields in the FieldFactory.

Let's see an example:

```php
namespace MyModels\Fields;

use SimpleCrud\FieldInterface;

/**
 * Format to store ips as numeric values
 */
class Ip extends SimpleCrud\Fields\Field
{
    public function dataToDatabase ($data)
    {
        return ip2long($data);
    }

    public function dataFromDatabase ($data)
    {
        return long2ip($data);
    }
}
```

Now, to use it:

```php
$db = new SimpleCrud\SimpleCrud($pdo);

//Get the field factory from the entity factory
$fieldFactory = $db->getEntityFactory()->getFieldFactory();

//Add the namespace of my custom fields
$fieldFactory->addNamespace('MyModels\\Fields\\');

//All fields named "ip" use the class "Ip"
$fieldFactory->addSmartName('ip', 'Ip');

//Use in the ip fields
$db->session->insert()
    ->data(['ip' => '0.0.0.0'])
    ->run();
```

### Create your own custom queries

To extend the Select query with custom methods:

```php
namespace MyModels\Queries;

use SimpleCrud\Queries\Mysql\SelectAll as BaseSelect;

class SelectAll extends BaseSelect
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

//Get the query factory from the entity factory
$fieldFactory = $db->getEntityFactory()->getQueryFactory();

//Add the namespace of my custom queries
$queryFactory->addNamespace('MyModels\\Queries\\');

//use in your select queries
$posts = $db->post->select()
    ->actived()
    ->olderThan(new Datetime('now'))
    ->run();
```


### Shared attributes

You may to share some values across all entities, rows and collections. For example a language configuration, the base path where the assets are stored, etc. To do that, there are the `getAttribute` and `setAttribute` methods:

```php
//Save an attribute, for example, the language code:
$db->setAttribute('language', 'en');

//This value is accessible from the entity class and row/rowCollection:
echo $db->post->getAttribute('language'); //en

//And rows
$post = $db->post[2];
$post->getAttribute('language'); //en
```

Note: The attributes are read-only for entities, rows and rowCollections, only the `SimpleCrud\SimpleCrud` instance has the method `setAttribute`.

