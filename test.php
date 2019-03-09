<?php
include 'vendor/autoload.php';

$pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');
$db = new SimpleCrud\SimpleCrud($pdo);

$newPost = $db->noticias_categorias->create(['titulo' => 'Titulo de teste 001']);
$newPost->save();