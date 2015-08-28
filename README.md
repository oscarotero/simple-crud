# SimpleCrud

[![Build Status](https://travis-ci.org/oscarotero/simple-crud.png?branch=master)](https://travis-ci.org/oscarotero/simple-crud)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/simple-crud/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/simple-crud/?branch=master)

PHP library to provide CRUD functions (Create, Read, Update, Delete) in Mysql/Sqlite databases with zero configuration and some magic.


## Components

SimpleCrud has the following classes:

* **SimpleCrud:** Manage the database connection, execute the queries and create all entities. 
* **Query:** Generates all queries needed for select/insert/update/delete/etc operations.
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

Use more advanced queries:

```php
//insert new posts
$db->insert('posts')
    ->data([
        'title' => 'My first post',
        'text' => 'This is the text of the post'
    ])
    ->run();

//Update a post
$db->update('posts')
    ->data([
        'title' => 'New title'
    ])
    ->where('id = :id', [':id' => 23])
    ->limit(1)
    ->run();

//Delete a post
$db->delete('posts')
    ->byId(23) //shortcut of where('id = :id', [':id' => 23])
    ->run();

//Select posts
$db->select('posts')
    ->where('id > :id', [':id' => 10])
    ->orderBy('id ASC')
    ->limit(100)
    ->all();
```

You can also select rows related with other rows:

```php
//Select a post by id
$post = $db->posts[23];

//Select the comments related with this posts
$comments = $db->select('posts')
    ->relatedWith($post)
    ->all();
```

You can, also create your own `Queries` methods to extend the default behaviour with custom functionalities. You only have to create methods starting by 'query' into your entity class

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    public function queryIsActive($query)
    {
        $query->where('active = 1');
    }
}
```

Ready to use:

```php
$posts = $db->select('posts')
    ->withId(34)
    ->isActive() //our method!
    ->one();
```

#### Working with rows

When you select data from the database, it's saved in a `Row` instance. This class allows read and modify the data:

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

You can set custom methods to row and rowcollections, just create methods starting by `row` and `rowCollection` into the entity.

```php
namespace MyModels;

use SimpleCrud\Entity;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;

class Posts extends Entity
{
    public function rowEscapeText($row)
    {
        $row->text = htmlspecialchars($row->text);
    }

    public function rowCollectionSumIds($collection) {
        return array_sum($collection->ids);
    }
}
```

Now, you can use this functions in the rows and rowcollections:

```php
$posts = $db->select('posts')
    ->byId([1, 2, 3])
    ->all();

$posts->escapeText(); //Execute escapeText in each row

$total = $posts->sumIds(); //Returns 6
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
$comments = $db->select('comments')->relatedWith($post)->all();

//Or even:
$comments = $post->select('comments')->all();
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

You can define the way of the lazy loads are executed, creating methods starting by "row" in the entity class. The result of the method will be cached in a property with the same name.
Lazy loads not only works with relations, but also with any value you want. Just create a method named row[NameOfTheProperty] and that's all.

```php
namespace MyModels;

use SimpleCrud\Entity;

class Posts extends Entity
{
    /**
     * Changed the way to return the comments related with this post
     * returns only the validated comments
     */
    public function rowComments($row)
    {
        return $row->select('comments')
            ->where('validated = 1')
            ->all();
    }

    /**
     * Custom method to return the title in lowercase
     */
    public function rowLowercaseTitle($row)
    {
        return strtolower($row->title);
    }
}
```

Now, to use it:

```php
//Select post id=4
$post = $db->posts[4];

$post->comments; //Execute rowComments() methods and save the result in $post->comments
$post->comments; //Access to the cached result instead execute rowComments() again
$post->lowercaseTitle; //Execute rowLowercaseTitle() and save the result in $post->lowercaseTitle;
```

The difference between execute `$post->comments()` or call directly `$post->comments` is that the second saves the result in the property so it's only executed the first time.

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

### Shared attributes

Sometimes, you want to share some values across all entities, rows and adapter. For example a language configuration, the basepath where the files are stored, etc. To do that, there are the `getAttribute` and `setAttribute` methods:

```php
//Save an attribute, for example, the language code:
$db->setAttribute('language', 'en');

//This value is accessible from the entity class and row/rowCollection:
echo $db->posts->getAttribute('language'); //en

//And rows
$post = $db->posts[2];
$post->getAttribute('language'); //en
```
Only the SimpleCrud instance has the method `setAttribute`, so only it can create/modify attributes. These values are inmutable for the rest.

Check the commented code to know full API.
