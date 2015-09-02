# SimpleCrud

[![Build Status](https://travis-ci.org/oscarotero/simple-crud.png?branch=master)](https://travis-ci.org/oscarotero/simple-crud)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/simple-crud/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/simple-crud/?branch=master)

PHP library to (Create, Read, Update, Delete) in Mysql/Sqlite databases with zero configuration and some magic.

## Naming conventions:

This library relies in some conventions for simplify (or avoid) the configuration.

* Table names SHOULD be in [singular](http://stackoverflow.com/a/5841297) and **camelCase**
* Fields names SHOULD be in **singular** and **camelCase**
* The primary key of all tables MUST be `id`.
* Foreign keys MUST be `[tableName]_id`. For example, `post` table uses `post_id` as foreign key.
* Associative tables MUST use a underscore joining the two tables in alphabetic order. For example, the relationship between `post` and `tag` is `post_tag` but `post` and `category` is `category_post`.

## Components

SimpleCrud has the following classes:

* **SimpleCrud:** Manage the database connection, execute the queries and create all entities. 
* **Query:** Creates the database queries needed. Currently there are support for mysql and sqlite
* **Entity:** Manages an entity (database table).
* **Row:** Stores/modifies the data of a row
* **RowCollection:** Is a collection of rows
* **Fields:** Converts the values before save into the database (for example: convert datetime values to be compatible with mysql)

## Usage example

Let's say we have the following database:

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

To use the database, just create an instance of `SimpleCrud\SimpleCrud` passing the `PDO` connection.

```php
use SimpleCrud\SimpleCrud;

$db = new SimpleCrud($pdo);

//To get any entity, just use the properties, they will be created on demand:
$post = $db->post;
```

SimpleCrud can detect automatically all relationship between tables using the naming conventions described above. To do that, uses the `foreignKey` know the relation type `RELATION_HAS_ONE | RELATION_HAS_MANY | RELATION_HAS_BRIDGE`. For example: the foreignKey for the table "category" is "category_id" and the table "post" has a field called "category_id", so SimpleCrud knows that each post has one category (`RELATION_HAS_ONE`).


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

#### Use queries:

Each time you use a method like `select()`, `update()`, `count()`, `delete()`, etc... an instance of a Query class is created. Each query has modifiers like `orderBy()`, `limit()`, etc, and the method `run()` to execute the query and return the result. For example:

```php
$query = $db->post->update();

//modify the query
$query
    ->data(['title' => 'New title'])
    ->where('id = :id', [':id' => 23])
    ->limit();

//you can stringify the query
echo $query; //UPDATE `posts` ...

//and run the query
$query->run();
```

Here's more examples:

```php
//insert new posts
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

The `select()` query has a lot of modifiers to add conditions, sort values, limit, etc. It has also the methods `all()` and `one()` to return all results of just the first one:

```php
$posts = $db->post->select()
    ->where('id > :id', [':id' => 10])
    ->orderBy('id ASC')
    ->limit(100)
    ->all();
```

The method `relatedWith()` allows select related data easily:

```php
//Select a post by id
$post = $db->post[23];

//Select the category related with this posts
$category = $db->category->select()
    ->relatedWith($post)
    ->one();
```

#### Working with rows

When you select data from the database using `one()`, it's saved in a `Row` instance. This class allows read and modify the data:

```php
$post = $db->post->select()->one();

echo $post->title; //Get the post title

$post->title = 'New title';

$post->save(); //Save the data

$post->delete(); //remove the row in the database

//Create a new row (but it's not inserted in the database yet)
$newPost = $db->post->create(['title' => 'The title']);

//Use an array to edit more values at once
$newPost->set([
    'title' => 'New title',
    'description' => 'Another description'
]);

$newPost->save(); //Insert the post in the database.
```

#### Working with collections

If you use `all()` to get the result of a selection, no mather how many rows are selected, you get a `RowCollection` instance (it can be empty, one result or more).

```php
$allPosts = $db->post->select()->all();

foreach ($allPosts as $post) {
    echo $post->title;
}
```

The collection allows to get arrays with the values of a column. For example, to get all titles:

```php
$allPosts->title; //array with all titles of all posts. The keys are the ids
```

#### Fields classes

The purpose of the `SimpleCrud\Fields` classes is to convert the data between the database and the entity. For example, in Mysql the format used to store datetime values is "Y-m-d H:i:s", so the class `SimpleCrud\Fields\Datetime converts any string or `Datetime` instance to this format. This conversion will be done just before execute the query and won't change the value of the `Row` instance. The available fields are:

* Boolean: To converts boolean values
* Field: It's the default field and keeps the value as is.
* Datetime: Converts a string or Datetime instance to "Y-M-d H:i:s"
* Date: Converts a string or Datetime instance to "Y-M-d"
* Set: an array of values to a string. For example: ['red', 'blue', 'green'] will be stored as "red,blue,green" in database.
* Integer: Converts values to integers or NULL
* Float: Converts values to float numbers or NULL
* Serializable: To store arrays or any other serializable data structure

If the fields in the entity are not specified, they will be asigned automatically according with the field type in the database. There is also a naming convention to set formats automatically to fields with the following names:

* Integer format will be asigned to fields named `id` or any field ending by `_id`.
* Datetime format will be asigned to fields named `pubdate`, `createdAt` and `updatedAt`.
* Boolean format will be asigned to fields named `active`

```php
//Create a post. We don't care about the Datetime
$post = $db->post->create([
    'title' => 'My post',
    'text' => 'My post text',
    'pubdate' => new Datetime('now')
]);

$post->save();
```

You can create more "smart names". This will be explained latter.

### Lazy loads

Both `Row` and `RowCollection` can load automatically the related rows if you call them by the entity name:

```php
//Get post id=34
$post = $db->post[34];

//Load the category related with this post
$category = $post->category;

//This is equivalent to:
$category = $db->category->select()->relatedWith($post)->one();

//To get the query instance (because you want to modify the selection):
$category = $post->select('category')->one();
```

This allows make awesome (and dangerous :D) things like this:

```php
$titles = $db->post[34]->tag->post->title;

//Get the post id=34
//Get the tags of the post
//Then the posts related with these tags
//And finally, the titles of all these posts
```

## Customization

SimpleCrud uses factory classes to create instances of entities, queries and fields. You can configure or create your own factories to customize how these instances are created. 

### EntityFactory

This class creates the instances of all entities. If it's not provided, by default uses the `SimpleCrud\EntityFactory` but you can create your own factory that must implements the `SimpleCrud\EntityFactoryInterface`. The default EntityFactory, can be configured using the following methods:

* `setNamespace` The namespace where your entity classes are located.
* `setAutocreate` To create instances of tables without entities associated
* `setFieldFactory` To set your own factory to create Field instances
* `setQueryFactory` To set your own factory to create Query instances

```php
//Instantiate the entity factory
$entityFactory = new SimpleCrud\EntityFactory();

//Set the namespace of the entities:
$entityFactory->setNamespace('App\\MyModels\\');

//Create the simplecrud instance
$db = new SimpleCrud\SimpleCrud($pdo, $entityFactory);

$db->post; //Returns an instance of App\MyModels\Post
```

By default, if the entityFactory is not passed to SimpleCrud, it creates one automatically as following:

```php
$entityFactory = new SimpleCrud\EntityFactory();

//Creates all undefined entities using SimpleCrud\Entity class
$entityFactory->setAutocreate('SimpleCrud\\Entity');
```

### QueryFactory

The query factory is the responsive to instantiate all query classes. By default uses the `SimpleCrud\QueryFactory` class but you can provide your own factory extending the `SimpleCrud\QueryFactoryInterface`. The default factory has the following options:

* `addNamespace` Add more namespaces where find more query classes.

Example:

```php
$entityFactory = new SimpleCrud\EntityFactory();
$queryFactory = new SimpleCrud\QueryFactory();

//Add a namespace with my custom query classes, with more options, etc
$queryFactory->addNamespace('App\\Models\\Queries\\');

//Add the queryFactory to the entityFactory:
$entityFactory->setQueryFactory($queryFactory);

//Now create the simpleCrud connection
$db = new SimpleCrud\SimpleCrud($pdo, $entityFactory);
```

### FieldFactory

This factory creates intances of the fields uses by the entities to convert the values. By default uses `SimpleCrud\FieldFactory` but you can create your own factory extending `SimpleCrud\FieldFactoryInstance`. The default FieldFactory has the following options:

* `addNamespace` Add more namespaces where find more field classes.
* `addSmartName` Add more smart names to asign automatically types to specific field names.

Example:

```php
$entityFactory = new SimpleCrud\EntityFactory();
$fieldFactory = new SimpleCrud\FieldFactory();

//Add a namespace with my custom field classes
$fieldFactory->addNamespace('App\\Models\\Fields\\');

//By default, all fields called "year" will be integer
$fieldFactory->addSmartName('year', 'Integer');

//Add the fieldFactory to the entityFactory:
$entityFactory->setFieldFactory($fieldFactory);

//Now create the simpleCrud connection
$db = new SimpleCrud\SimpleCrud($pdo, $entityFactory);
```

## Create your own entities

The default behaviour of simpleCrud is fine but you may want to extend the entities with your own methods, validate data, etc. So you need to create classes for your entities. The entity classes must extend the `SimpleCrud\Entity` class and be named like the database table (with uppercase first letter). For example, for a table named `post` you need a class named `Post`. In the Entity class you can configure the types of the fields and add your own methods:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public $fields = [
        'id' => 'Integer',
        'createdAt' => 'Datetime',
        'updatedAt' => 'Datetime',
        'active' => 'Boolean',
        'text' => 'Field',
    ];

    public function getLatests()
    {
        return $this->select()
            ->orderBy('createdAt DESC')
            ->limit(10)
            ->all();
    }
}
```

Now if you configure the EntityFactory to look into `MyModels` namespace, it will use this class when you need `$db->post` entity:

```php
$entityFactory = new SimpleCrud\EntityFactory();

$entityFactory->setNamespace('MyModels\\');

$db = new SimpleCrud\SimpleCrud($pdo, $entityFactory);

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

The Entity has the method `init` that you can use to initialize things. It's called at the end of the `__construct`. For example, you can use your own `Row` and `RowCollection` classes or customize them adding more methods:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public function init()
    {
        //Add some methods to collections
        $this->collection->registerMethod('sumIds', function ($collection) {
            return array_sum($collection->id);
        });

        //Add some properties to row
        $this->row->registerProperty('titleLowerCase', function ($row) {
            return strtolower($row->title);
        });
    }
}
```

Now, on use the entity:

```php
$posts = $db->post->select()->all();

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
class Ip implements FieldInterface
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
$entityFactory = new SimpleCrud\EntityFactory();
$fieldFactory = new SimpleCrud\FieldFactory();

//Add the namespace of my custom fields
$fieldFactory->addNamespace('MyModels\\Fields\\');

//All fields named "ip" use the class "Ip"
$fieldFactory->addSmartName('ip', 'Ip');

$entityFactory->setFieldFactory($fieldFactory);

$db = new SimpleCrud\SimpleCrud($pdo, $entityFactory);

//Use in the ip fields
$db->session->insert()
    ->data(['ip' => getCurrentIp()])
    ->run();
```

### Create your own custom queries

Because it's the same than for Fields, let's extend a Select query with custom methods:

```php
namespace MyModels\Queries;

use SimpleCrud\Queries\Select;

class Select extends Select
{
    public function isActive()
    {
        return $this->where('active = 1');
    }

    public function olderThan(\Datetime $date)
    {
        return $this->where('createdAt < :date', [':date' => $date->format('Y-m-d H:i:s')]);
    }
}

Now to use it:

```php
$entityFactory = new SimpleCrud\EntityFactory();
$queryFactory = new SimpleCrud\QueryFactory();

//Add the namespace of my custom queries
$fieldFactory->addNamespace('MyModels\\Queries\\');

$entityFactory->setQueryFactory($queryFactory);

$db = new SimpleCrud\SimpleCrud($pdo, $entityFactory);

//use in your select queries
$posts = $db->post->select()
    ->isActive()
    ->olderThan(new Datetime('now'))
    ->orderBy('id DESC')
    ->all();
```


### Shared attributes

Sometimes, you want to share some values across all entities, rows and collections. For example a language configuration, the basepath where the files are stored, etc. To do that, there are the `getAttribute` and `setAttribute` methods:

```php
//Save an attribute, for example, the language code:
$db->setAttribute('language', 'en');

//This value is accessible from the entity class and row/rowCollection:
echo $db->post->getAttribute('language'); //en

//And rows
$post = $db->post[2];
$post->getAttribute('language'); //en
```

Note: The attributes are read-only for entities, rows and rowCollections, only the `SimpleCrud\SimpleCrud` instance has the method `setAttribute`. This ensures than an attribute can be changed for a entity and affect to other entities.

Check the doc comment code to know full API.
