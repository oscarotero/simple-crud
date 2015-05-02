# SimpleCrud

[![Build Status](https://travis-ci.org/oscarotero/simple-crud.png?branch=master)](https://travis-ci.org/oscarotero/simple-crud)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/simple-crud/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/simple-crud/?branch=master)

PHP library to provide some CRUD functions (Create, Read, Update, Delete) in Mysql/Sqlite databases.


## Components

SimpleCrud has the following classes:

* **Adapters:** Manage the database connection, execute the queries and create all entities. Currently there are two adapters: for mysql and sqlite databases
* **Entity:** Manages an entity (database table) to select, insert, update, delete rows.
* **Row:** Stores/modifies the data of a row
* **RowCollection:** Is a collection of rows
* **Fields:** Converts the values before save into the database (for example: convert datetime values to be compatible with mysql)


### Define the entities:

Create a new entity for each table in the database in a common namespace:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public $table = 'posts';
    public $foreignKey = 'posts_id';
    public $fields = [
        'id',
        'title',
        'text',
        'users_id'
    ];
}

class Comments extends Entity
{
    public $table = 'comments';
    public $foreignKey = 'comments_id';
    public $fields = [
        'id',
        'text',
        'posts_id',
        'users_id'
    ]
}

class Users extends Entity
{
    public $table = 'users';
    public $foreignKey = 'users_id';
    public $fields = [
        'id',
        'name'
    ]
}
```

SimpleCrud uses the `foreignKey property` to detect automatically the relationship between two entities `RELATION_HAS_ONE | RELATION_HAS_MANY`. For example: the foreignKey in Posts is "posts_id" and Comments has a field called "posts_id", so SimpleCrud knows that each comment can have one related post (`RELATION_HAS_ONE`).

You can define also entities without these properties:

```php
class Tags extends Entity {
    //If table is not defined, by default get the lowercased class name (tags)
    //If foreignKey is not defined, by default get the table + _id (tags_id)
    //If fields are not defined, get them from the database
}
```

This is usefull in early phases, when the database can change and you don't want edit the entity class all the time. You even can use this library with no entities and the "autocreate" option enabled (to get all tables from the database and create the entities  automatically).

### Init the library

Let's create an instance of the Adapter, passing the PDO instance with the database connection and an instance of EntityFactory to create the entities.

```php
use SimpleCrud\Adapters\Mysql;
use SimpleCrud\EntityFactory;

$db = new Mysql($PDO, new EntityFactory([
    'namespace' => 'MyModels\\' //The namespace where my entities classes are defined
    'autocreate' => true //Set true to create automatically non defined entities.
]));

//You can access to all entities, they will be instanced on demand:
$db->posts; //Posts entity
```

To init the library with no entities defined (and create it automatically):

```php
use SimpleCrud\Adapters\Mysql;

$db = new Mysql($PDO);

//All entities will be created automatically using the database tables:
$db->posts; //Posts entity
```

### Using the library

#### Create and edit values:

```php
//Create a new post
$post = $db->posts->create([
    'title' => 'My first post',
    'text' => 'This is the text of the post'
]);

//Get/set values
echo $post->title; //My first item

$post->description = 'New description';

//Or use an array for edit values
$post->set([
    'title' => 'New title',
    'description' => 'Another description'
]);

//Save (insert/update) the post in the database
$post->save();

//Delete the post in the database
$post->delete();
```

#### selectBy

`selectBy` is a function to select values by keys:

```php
//Select the post with id = 45
$post = $db->posts->selectBy(45);

//Or select various ids and returns a RowCollection
$posts = $db->posts->selectBy([45, 34, 98]);
```

`selectBy` can be used with `Row` and `RowCollection` instances to select related rows:

```php
//Get the post id=5
$post = $db->posts->selectBy(5);

//Get all comments related with this post
$comments = $db->comments->selectBy($post); 

//Get a RowCollection with 4 posts
$posts = $db->posts->selectBy([5, 6, 7, 8]);

//Get all comments related with these 4 posts
$comments = $db->comments->selectBy($posts);
```

`selectBy` has more arguments to define WHERE, ORDER BY and LIMIT clauses:

```php
//Select a post:
$post = $db->posts->selectBy(5);

//Select related comments where "enable = 1"
$comments = $db->comments->selectBy($post, 'enable = 1');

//Or provide marks:
$comments = $db->comments->selectBy($post, 'enable = :enable', [':enable' => 1]);

//And sort the result
$comments = $db->comments->selectBy($post, 'enable = :enable', [':enable' => 1], 'pubdate DESC');

//And limit
$comments = $db->comments->selectBy($post, 'enable = :enable', [':enable' => 1], 'pubdate DESC', 10);
```

#### select

The function `select` has the same arguments but the first:

```php
//SELECT * FROM posts WHERE slug = 'hello-world' ORDER BY id DESC LIMIT 10
$post = $db->posts->select('slug = :slug', [':slug' => 'hello-world'], 'id DESC', 10);

//SELECT * FROM posts WHERE id = :id LIMIT 1
$post = $db->posts->select("id = :id", [':id' => 45], null, true);
// (*) the difference between limit = 1 and limit = true is that true returns the fetched item and 1 returns an rowCollection with 1 element
```

Both `select` and `selectBy` functions accepts two more arguments:

* `$join`: Allows select more entities in the same query (using LEFT JOIN)
* `$from`: To add more tables that you can use in the WHERE clause

```php
//SELECT {all fields from posts and users} FROM posts LEFT JOIN users ON posts.users_id = users.id WHERE active = 1 ORDER BY id DESC LIMIT 10
$posts = $db->posts->select('active = 1', null, 'id DESC', 10, ['users']);
```

#### Other methods

`fetchOne` and `fetchAll` allows create the query yourself:

```php
$post = $db->posts->fetchOne('SELECT * FROM posts WHERE users_id  :users_id LIMIT 1', [':users_id' => 3]);

//Simplecrud accepts arrays in the marks:
$posts = $db->posts->fetchAll('SELECT * FROM posts WHERE users_id IN (:users_id)', [':users_id' => [3, 4, 5]]);
```

To insert, update or delete rows without select them, use directly the entity:

```php
$db->posts->delete('id > :id', [':id' => 23], 10);
//DELETE FROM `posts` WHERE id > 23 LIMIT 10

$db->posts->update(['text' => 'Hello world'], 'id = :id', [':id' => 23], 1);
//UPDATE `posts` SET `text` = 'Hello world' WHERE id = 23 LIMIT 1

$id = $db->posts->insert(['text' => 'Hello world']);
//INSERT INTO `posts` (`text`) VALUES ('Hello world')
```


### Validate data

Each entity has two methods to convert/validate data before push to database and after pull from it. You can overwrite this methods to customize its behaviour:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public $fields = [
        'id',
        'title',
        'text',
        'pubDate',
        'latestUpdate',
        'users_id'
    ];

    public function dataToDatabase (array $data, $new)
    {
        $data['latestUpdate'] = date('Y-m-d H:i:s');

        if ($new) { //it's an insert
             $data['pubDate'] = $data['latestUpdate'];
        } else if ($data['pubDate'] instanceof Datetime) {
            $data['pubDate'] = $data['pubDate']->format('Y-m-d H:i:s');
        }

        return $data;
    }

    public function dataFromDatabase (array $data)
    {
        $data['latestUpdate'] = new DateTime($data['latestUpdate']);
        $data['pubDate'] = new DateTime($data['pubDate']);

        return $data;
    }
}
```

### Customize the Row and RowCollection classes

Each entity can use its own `Row` or `RowCollection` classes instead the defaults, to create custom methods. You need to create classes extending `SimpleCrud\Row` and `SimpleRow\CollectionRow`:

```php
namespace MyModels;

use SimpleCrud\Entity;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;

class Posts extends Entity
{
    public $rowClass = 'MyCustomRowClass';
    public $rowCollectionClass = 'MyCustomRowCollectionClass';
}

class MyCustomRowClass extends Row
{
    public function escapeText ()
    {
        $this->text = htmlspecialchars($this->text);
    }
}

class MyCustomRowCollectionClass extends Row
{
    public function getSumIds ()
    {
        $ids = $this->get('id');

        return array_sum($ids);
    }
}
```

Now, you can use this functions in the rows and rowcollections:

```php
$posts = $db->posts->selectBy([1, 2, 3]);

$posts->escapeText(); //Execute escapeText in each row

$total = $posts->getSumIds(); //Returns 6
```

You can set these classes automatically creating classes with the same name than the entity class but in a subnamespace called "Rows" or "RowCollections":

The custom Row class for Posts entity:

```php
namespace MyModels\Rows;

use SimpleCrud\Row;

class Posts extends Row
{
    //row custom methods
}
```

The custom RowCollection class for Posts entity:

```php
namespace MyModels\RowCollections;

use SimpleCrud\RowCollection;

class Posts extends RowCollection
{
    //row custom methods
}
```

You can define also the classes `MyModels\Rows\Row` and `MyModels\RowCollections\RowCollection` to be used as default instead `SimpleCrud\Row` and `SimpleCrud\RowCollection`.


### Lazy loads

Both `Row` and `RowCollection` can load automatically the related rows if you call them by the entity name:

```php
//Get posts by id=34
$post = $db->posts->selectBy(34);

//Load the comments related with this post
$comments = $post->comments;

//This is equivalent to:
$comments = $db->comments->selectBy($post);
```

This allows make awesome (and dangerous :D) things like this:

```php
//Select the post id=34
$post = $db->posts->selectBy(34);

$title = $post->comments->users->posts->title;

//Get the comments of the post
//Then the users related with these comments
//Then the posts related by these users
//And finally, the titles of all these posts
```

You can define the way of the lazy loads are executed, creating methods starting by "get" in the row class. The result of the method will be cached in the property.
Lazy loads not only works with relations, but also with any property you want. Just create a method named get[NameOfTheProperty] and you get it.

```php
namespace MyModels\Rows;

use SimpleCrud\Row;

class Posts extends Row
{
    /**
     * Changed the way to return the comments related with this post
     * returns only the validated comments
     */
    public function getComments ()
    {
        //Use $this->getAdapter() to access to the database adapter
        return $this->getAdapter()->comments->selectBy($this, "validated = 1");
    }

    /**
     * Custom method to return the title in lowercase
     */
    public function getLowercaseTitle ()
    {
        return strtolower($this->title);
    }
}
```

Now, to use it:

```php
//Select post id=4
$post = $db->posts->selectBy(4);

$post->comments; //Execute getComments() methods and save the result in $post->comments
$post->comments; //Access to the cached result instead execute getComments() again
$post->lowercaseTitle; //Execute getLowercaseTitle() and save the result in $post->lowercaseTitle;
```
The difference between execute `$post->getComments()` or call directly `$post->comments` is that the second saves the result in the property so it's only executed the first time.

Note that all these `getWhatever` methods that get relations are available automatically even if you don't define them.

So, let's see three ways to return the users related with a post:

```php
//select post id=4
$post = $db->posts->selectBy(4);

//1: Using selectBy
$users = $db->users->selectBy($post);

//2: Using the magic property
$users = $post->users;

//3: Using the magic method
$users = $post->getUsers();

//3a: The magic method allows arguments like selectBy:
$users = $post->getUsers('active = :active', [':active' => 1]);
```

### Fields

The purpose of the `SimpleCrud\Fields` classes is to convert the data between the database and the entity. For example, in Mysql the format used to store datetime values is "Y-m-d H:i:s", so the class `SimpleCrud\Fields\Datetime converts any string or `Datetime` instance to this format. This conversion will be done just before execute the query and wont change the value of the `Row` instance. The available fields are:

* Field: It's the default field and keeps the value as is.
* Datetime: Converts a string or Datetime instance to "Y-M-d H:i:s"
* Date: Converts a string or Datetime instance to "Y-M-d"
* Set: an array of values to a string. For example: ['red', 'blue', 'green'] will be stored as "red,blue,green" in database.

If the fields in the entity are not specified, they will be asigned automatically according with the field type in the database. If you prefer define the field types by yourself, you can do it in this way:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public $table = 'posts';
    public $foreignKey = 'posts_id';
    public $fields = [
        'id',
        'title',
        'text',
        'pubdate' => 'datetime',
        'types' => 'set'
    ];
}
```

Usage example:

```php
//Create a post. We don't care about the Datetime and Set fields:
$post = $db->posts->create([
    'title' => 'My post',
    'text' => 'My post text',
    'pubdate' => new Datetime('now'),
    'types' => ['image', 'video']
]);

$post->save();
```

### Custom fields types

You can create your own fields types or overwrite the existing ones. SimpleCrud will search in the namespace ```[your-entities-namespace]\Fields\``` for your custom classes. All these classes must implements `SimpleCrud\Fields\FieldInterface`.

Let's see an example:

```php
namespace MyModels\Fields;

use SimpleCrud\Fields\FieldInterface;

/**
 * Format to serialize data before save in the database
 */
class Serializable implements FieldInterface
{
    public function dataToDatabase ($data)
    {
        return serialize($data);
    }

    public function dataFromDatabase ($data)
    {
        return unserialize($data);
    }
}
```

Use the new "serializable" field type in your entities:

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public $table = 'posts';
    public $foreignKey = 'posts_id';
    public $fields = [
        'id',
        'text',
        'data' => 'serializable'
    ];
}
```

Ready to use:

```php
//Create a post with serializable data, for example an array:
$post = $db->posts->create([
    'text' => 'My post',
    'data' => ['foo', 'bar']
]);

//Our custom field will serialize the data before save it
$post->save();

//Get the post from the database:
$post = $db->posts->selectBy(1);

//We have the array
var_dump($post->data); //array('foo', 'bar')
```

If you create a Field in your namespace with the same name than any of the defaults fields (Date, Datetime, Set, etc), SimpleCrud will choose your custom Field instead the default, so this is useful to overwrite the default behaviours.

### Shared attributes

Sometimes, you want to share some values across all entities, rows and adapter. For example a language configuration, the basepath where the files are stored, etc. To do that, there are the `getAttribute` and `setAttribute` methods:

```php
//Save an attribute, for example, the language code:
$db->setAttribute('language', 'en');

//This value is accessible from all entitites:
echo $db->posts->getAttribute('language'); //en

//And rows
$post = $db->posts->selectBy(2);
$post->getAttribute('language'); //en
```
Only the adapter has the method `setAttribute`, so only it can create/modify attributes.

Check the commented code to know full API.
