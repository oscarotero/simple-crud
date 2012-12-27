OpenTraits\Crud
===============

OpenTraits is a set of generical and universal PHP traits to give some features to your classes without depending of a specific framework.
It uses the php 5.4 traits feature because this allows to combine different traits in just one class.

This set of traits provides some CRUD functions (Create, Read, Update, Delete).

Requirements
------------

* PHP 5.4 or newer
* Any PSR-0 compatible autoloader

OpenTraits\Crud\Mysql
---------------------

Provide CRUD functions with mysql database. Example:

```php

//Let's create our custom class:

class Item {
	use OpenTraits\Crud\Mysql;
}

//Configure the database and (optionally) define the table name and fields name.

Item::setDb($Pdo, 'items', ['id', 'title', 'text']);

//Create a new item

$Item = Item::create([
	'title' => 'My first item',
	'text' => 'This is a demo of crud'
]);

//Get/Set values
echo $Item->title; //My first item
$Item->description = 'New description';

//Save (insert/update) the item in the database
$Item->save();

//Select an item from database by id
$Item = Item::selectById(34);

//Select one item from database by custom query:
$Item = Item::selectOne('WHERE title LIKE :title', [':title' => 'My first item']);

//Select all items
$items = Item::select();

//Select some items
$items = Item::select('WHERE id != :id', [':id' => 45]);

//Using array as queries:
$query = [
	'WHERE' => [
		'id > :id_start',
		'id < :id_end'
	],
	'SORT BY' => 'title',
	'LIMIT' => 2
];
$items = Item::select($query, [':id_start' => 10, ':id_end' => 24]);

//Delete the item
$Item = Item::selectById('34');
$Item->delete();

//Validate or convert data before saving using the method prepareToSave:
class Item {
	use OpenTraits\Crud\Mysql;

	public function prepareToSave (array $data) {
		if ($data['no-save']) {
			$this->setError('This item cannot be saved!');
			return false;
		}

		if (!$data['datetime']) {
			$data['datetime'] = date('Y-m-d H:i:s');
		}

		return $data;
	}
}

Item::setDb($Pdo, 'items', ['id', 'title', 'text', 'no-save', 'datetime']);

$Item = Item::create([
	'title' => 'Title',
	'no-save' => true
]);

if (!$Item->save()) {
	echo $Item->getError(); //This item cannot be saved!
}
```