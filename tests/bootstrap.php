<?php
error_reporting(E_ALL);

include_once dirname(__DIR__).'/src/autoloader.php';
include_once __DIR__.'/entities.php';

//create de database
$pdo = new PDO('mysql:host=localhost;charset=UTF8', 'root', '');

$pdo->exec('DROP DATABASE IF EXISTS simplecrud_test');
$pdo->exec('CREATE DATABASE simplecrud_test DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci');

unset($pdo);

shell_exec('mysql -uroot simplecrud_test < '.__DIR__.'/db.sql');

PHPUnit_Framework_Error_Notice::$enabled = true;
