<?php
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');

function autoload ($className) {
	$className = ltrim($className, '\\');
	$fileName  = '';
	$namespace = dirname(__DIR__).'/';
	
	if ($lastNsPos = strripos($className, '\\')) {
		$namespace .= substr($className, 0, $lastNsPos);
		$className = substr($className, $lastNsPos + 1);
		$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	}

	$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

	if (is_file($fileName)) {
		require $fileName;
	}
}

spl_autoload_register('autoload');

class table {
	use OpenTraits\Crud\Mysql;
	use OpenTraits\Crud\Relations;
}

Table::setConnection(new PDO('mysql:dbname=items;host=localhost', 'root', 'root'));

class Item extends Table {
	static public $table = 'item';
	static public $fields;
	static public $relation_field;

	public function prepareToSave (array $data) {
		if ($data['id'] && ($data['id'] % 2)) {
			echo 'The id is odd';
			return false;
		}

		return $data;
	}
}

class Comment extends Table {
	static public $table = 'comment';
	static public $fields;
	static public $relation_field;
}

$Comment = Comment::create([
	'text' => 'Ola'
]);

$Item = Item::create([
	'title' => 'Random title: '.uniqid()
]);

$Comment->relate($Item);
$Comment->save();

$Item->save();

$Item->edit(['text' => 'lorem ipsum']);

$Item->save();

$Item2 = Item::selectById($Item->id);

$someItems = Item::select(array(
	'where' => [
		'id > 5',
		'id < 10'
	]
));

foreach ($someItems as $Item) {
	echo $Item->id.'<br>';
}
