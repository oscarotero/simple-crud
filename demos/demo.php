<?php
ini_set('display_errors', 'On');

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

class Item {
	use OpenTraits\Crud\Mysql;

	public function prepareToSave (array $data) {
		if ($data['id'] && ($data['id'] % 2)) {
			$this->setError('The id is odd');
			return false;
		}

		return $data;
	}
}

Item::setDb(new PDO('mysql:dbname=items;host=localhost', 'root', 'root'));

$Item = Item::create([
	'title' => 'Random title: '.uniqid()
]);

$Item->save();

$Item->edit(['text' => 'lorem ipsum']);

if (!$Item->save()) {
	echo $Item->getError();
}

$Item2 = Item::selectById($Item->id);

$someItems = Item::select(array(
	'WHERE' => [
		'id > 5',
		'id < 10'
	]
));

foreach ($someItems as $Item) {
	echo $Item->id;
}