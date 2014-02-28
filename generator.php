<?php
require_once 'SimpleCrud/autoloader.php';

array_shift($argv);

$config = [
	'dns' => '',
	'user' => '',
	'pass' => '',
	'path' => '',
	'ns' => ''
];

while ($var = array_shift($argv)) {
	switch ($var) {
		case '-dns':
		case '-user':
		case '-pass':
		case '-path':
		case '-ns':
			$config[substr($var, 1)] = array_shift($argv);
			break;

		default:
			throw new Exception("Option '$var' not valid");
	}
}

$pdo = new PDO($config['dns'], $config['user'], $config['pass']);

$generator = new SimpleCrud\EntityGenerator($pdo, $config['path'], $config['ns']);

$generator->generate();