# SimpleCrud

[![Build Status](https://travis-ci.org/oscarotero/simple-crud.png?branch=master)](https://travis-ci.org/oscarotero/simple-crud)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/simple-crud/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/simple-crud/?branch=master)

PHP library to provide CRUD functions (Create, Read, Update, Delete) in Mysql/Sqlite databases with some magic features.


## Components

SimpleCrud has the following classes:

* **SimpleCrud:** Manage the database connection, execute the queries and create all entities. 
* **Query:** Generates all queries needed for select/insert/update/delete operations.
Currently there are support for mysql and sqlite databases
* **Entity:** Manages an entity (database table) to select, insert, update, delete rows.
* **Row:** Stores/modifies the data of a row
* **RowCollection:** Is a collection of rows
* **Fields:** Converts the values before save into the database (for example: convert datetime values to be compatible with mysql)


### Define the entities:

Create a new entity class for each table in the database in a common namespace, for example `MyModels`:

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

SimpleCrud uses the `foreignKey` property to detect automatically the relationship between two entities `RELATION_HAS_ONE | RELATION_HAS_MANY`. For example: the foreignKey in Posts is "posts_id" and Comments has a field called "posts_id", so SimpleCrud knows that each comment belongs to one post (`RELATION_HAS_ONE`).

You can define also entities without these properties:

```php
class Tags extends Entity {
    //If table is not defined, by default get the lowercased class name (tags)
    //If foreignKey is not defined, by default get the table + _id (tags_id)
    //If fields are not defined, get them from the database
}
```

This is usefull in early phases, when the database can change and you don't want edit the entity class all the time. You even can use this library with no entities and the "autocreate" option enabled (to get all tables from the database and create the entities automatically).

### Init the library

Let's create an instance of `SimpleCrud\SimpleCrud`, passing the `PDO` instance and an instance of `SimpleCrud\Factory`, used to create the entities/fields/queries instances:

```php
use SimpleCrud\SimpleCrud;
use SimpleCrud\Factory;

$factory = (new Factory())
    ->entities('MyModels\\') //The namespace where my entities classes are defined
    ->autocreate();          //Create automatically non defined entities.

$db = new SimpleCrud($PDO, $factory);

//You can access to all entities, they will be created on demand:
$db->posts; //Posts entity
```

You can avoid the factory if you don't have entities classes defined and want to create them automatically

```php
use SimpleCrud\SimpleCrud;

$db = new SimpleCrud($PDO);

//All entities will be created automatically using the database tables:
$db->posts; //Posts entity
```

### Using the library

#### Basic CRUD:

You can work directly with the entities to insert/update/delete/select data:

Use arrayAccess interface for access to the data using the `id`:

```php
//Get the entity to work with the table `posts`:
$posts = $db->posts;

//Get the post id = 3;
$post = $posts[3];

//Check if a row exists
if (isset($posts[3])) {
    echo 'exists';
}

//Delete a post
unset($posts[3]);

//Create or update posts
$posts[3] = [
    'title' => 'Hello world'
];

//Insert a new post
$posts[] = [
    'title' => 'Hello world 2'
];
```

Use more advanced queries:

```php
//Get the entity to work with the table `posts`:
$posts = $db->posts;

//insert new posts
//Entity::insert(array $data)

$posts->insert([
    'title' => 'My first post',
    'text' => 'This is the text of the post'
]);

//Update a post
//Entity::update(array $data, $where = null, $marks = null, $limit = null)

$posts->update(['title' => 'New title'], 'id = :id', [':id' => 23], 1);

//Delete a post
//Entity::delete($where = null, $marks = null, $limit = null)

$posts->delete('id = :id', [':id' => 23], 1);

//Select posts
//Entity::select($where = null, $marks = null, $orderBy = null, $limit = null)

$posts = $posts->select('id > :id', [':id' => 10], 'id ASC', 100);

//use limit = true to get just one post
$post = $posts->select('id = :id', [':id' => 10], null, true);
```

#### Working with queries

To work with more complicated queries, you can use the query functions:

```php
//Insert data
$db->insert('posts')
    ->data($myData)
    ->run();

//Update data
$db->update('posts')
    ->data($myData)
    ->where('id = :id', [':id' => 34])
    ->limit(1)
    ->run();

//Delete data
$db->delete('posts')
    ->where('active = 0')
    ->where('pub_date < :pubdate', [':pubdate' => $my_pubdate])
    ->limit(10)
    ->offset(5)
    ->run();

//Select data
$result = $db->select('posts')
    ->where('pub_date < :pubdate_max')
    ->where('id < :id_max')
    ->marks([
        ':pubdate_max' => $pubdate,
        ':id_max' => $id,
    ])
    ->limit(10)
    ->orderBy('id DESC')
    ->all();
```

This functions returns an instance of one of the `Queries` classes available. The `Select` class, used to read data from the database has some special methods:

```php
$result = $db->select('posts')
    ->withId(23) //select by id
    ->with('slug', $my_slug) //select by slug also
    ->one(); //get one result, instead all
```

You can also select rows related with other rows:

```php
//Select a post by id
$post = $db->posts[23];

//Select the comments related with this posts
$comments = $db->select('posts')->relatedWith($post)->all();
```

You can, also create your own `Queries` classes to extend the default behaviour with custom functionalities:

```php
namespace MyModels\Queries;

class Select extends \SimpleCrud\Queries\Mysql\Select
{
    public function isActive()
    {
        return $this->where('active = 1');
    }
}
```

To use it, you must register your Queries namespace in the factory and that's all:

```php
$factory = (new Factory())
    ->entities('MyModels\\Queries\\') //The namespace where my Queries classes are placed
    ->autocreate();          //Create automatically non defined entities.

$db = new SimpleCrud($PDO, $factory);

$posts = $db->select('posts')
            ->withId(34)
            ->isActive()
            ->one();
```

#### Working with rows

If you select a row from the database, it's saved in a `Row` instance. This class allows read the data and modify:

```php
$post = $db->posts[25];

echo $post->title; //Get the post title

$post->title = 'New title';

$post->save(); //Save the data

$post->delete(); //remove the row in the database

//Create a new row
$newPost = $db->posts->create(['title' => 'The title']);

$newPost->description = 'Hello world';

//Or use an array for edit values
$newPost->set([
    'title' => 'New title',
    'description' => 'Another description'
]);

$newPost->save(); //save the posts in the database.
```

#### Working with row collections

If you select more than one row from the database, the rows are saved in a `RowCollection` instance.

```php
$allPosts = $db->select('posts')->all();

foreach ($allPosts as $post) {
    echo $post->title;
}

$allTitles = $allPosts->title; //array with all titles of all posts
```


### Customize the Row and RowCollection classes

Each entity can use its own `Row` or `RowCollection` classes instead the defaults, to use custom methods. You need to create classes extending `SimpleCrud\Row` and `SimpleRow\CollectionRow`:

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
$posts = $db->select('posts')->byId([1, 2, 3])->all();

$posts->escapeText(); //Execute escapeText in each row

$total = $posts->getSumIds(); //Returns 6
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

### Lazy loads

Both `Row` and `RowCollection` can load automatically the related rows if you call them by the entity name:

```php
//Get posts by id=34
$post = $db->posts[34];

//Load the comments related with this post
$comments = $post->comments;

//This is equivalent to:
$comments = $db->comments->selectQuery->relatedWith($post)->all();
```

This allows make awesome (and dangerous :D) things like this:

```php
$titles = $db->posts[34]->comments->users->posts->title;

//Get the post id=34
//Get the comments of the post
//Then the users related with these comments
//Then the posts related by these users
//And finally, the titles of all these posts
```

You can define the way of the lazy loads are executed, creating methods starting by "get" in the row class. The result of the method will be cached in a property with the same name.
Lazy loads not only works with relations, but also with any value you want. Just create a method named get[NameOfTheProperty] and you get it.

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
        //Use $this->getDb() to access to the SimpleCrud instance
        return $this->select('comments')
            ->where('validated = 1')
            ->all();
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
$post = $db->posts[4];

$post->comments; //Execute getComments() methods and save the result in $post->comments
$post->comments; //Access to the cached result instead execute getComments() again
$post->lowercaseTitle; //Execute getLowercaseTitle() and save the result in $post->lowercaseTitle;
```
The difference between execute `$post->getComments()` or call directly `$post->comments` is that the second saves the result in the property so it's only executed the first time.

Note that all these `getWhatever` methods that get relations are available automatically even if they are not defined. For example:

```php
//select post id=4
$post = $db->posts[4];

$users = $post->select('users')->where('active = :active', [':active' => 1])->all();
```

### Fields

The purpose of the `SimpleCrud\Fields` classes is to convert the data between the database and the entity. For example, in Mysql the format used to store datetime values is "Y-m-d H:i:s", so the class `SimpleCrud\Fields\Datetime converts any string or `Datetime` instance to this format. This conversion will be done just before execute the query and won't change the value of the `Row` instance. The available fields are:

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
        'id' => 'field',
        'title' => 'field',
        'text' => 'field',
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

You can create your own fields types or overwrite the existing ones. You have to register the namespace with your custom fields in the factory.

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
        'id' => 'field',
        'text' => 'field',
        'data' => 'serializable'
    ];
}
```

Ready to use:

```php
$factory = (new Factory())
    ->fields('MyModels\\Fields\\') //The namespace where my Fields classes are placed
    ->autocreate();          //Create automatically non defined entities.

$db = new SimpleCrud($PDO, $factory);


//Create a post with serializable data, for example an array:
$post = $db->posts->create([
    'text' => 'My post',
    'data' => ['foo', 'bar']
]);

//Our custom field will serialize the data before save it
$post->save();

//Get the post from the database:
$post = $db->posts[1];

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
$post = $db->posts[2];
$post->getAttribute('language'); //en
```
Only the SimpleCrud instance has the method `setAttribute`, so only it can create/modify attributes. These values are inmutable for the rest.

Check the commented code to know full API.
