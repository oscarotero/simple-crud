<?php
include 'src/autoloader.php';

use SimpleCrud\SimpleCrud;
use SimpleCrud\Factory;

$pdo = new PDO('mysql:host=localhost;dbname=dag;charset=UTF8', 'root');

$factory = (new Factory())->autocreate();
$db = new SimpleCrud($pdo, $factory);

$result = $db->news->select()->one();

var_dump($result);


return;
//ciencia ficción:

$pdo = new PDO();
$factory = Factory::create()
	->entities('MyNamespace\\Entities')
	->queries('MyNamespace\\Queries')
	->fields('MyNamespace\\Fields')
	->autocreate();

$db = new SimpleCrud($pdo, $factory);

$posts = $db->posts;

$posts->select('pirolas verdes');  // Query\Insert::exec() -> all
$posts->select('pirolas verdes', true);  // Query\Insert::exec() -> one

$posts
	->select()
	->byId()
	->relatedWith()
	->where()
	->limit(1)
	->one();

$posts->insert($data); // Query\Insert::exec()
$posts->insert()->data()->run();


$post = $posts->selectBy(1);

