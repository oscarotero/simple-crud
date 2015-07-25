<?php
error_reporting(E_ALL);

include_once dirname(__DIR__).'/src/autoloader.php';

PHPUnit_Framework_Error_Notice::$enabled = true;

function initSqlitePdo()
{
    $pdo = new PDO('sqlite::memory:');

    $pdo->beginTransaction();

    //Posts
    $pdo->exec(<<<EOT
        CREATE TABLE "posts" (
            `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `title` TEXT,
            `categories_id` INTEGER,
            `pubdate`   TEXT,
            `type`  TEXT,
            FOREIGN KEY(`categories_id`) REFERENCES categories(id)
        );
EOT
);

    //Categories
    $pdo->exec(<<<EOT
        CREATE TABLE `categories` (
            `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `name`  TEXT
        );
EOT
);

    //Tags
    $pdo->exec(<<<EOT
        CREATE TABLE `tags` (
            `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `name`  TEXT
        );
EOT
);

    //Relationship Tags-Posts
    $pdo->exec(<<<EOT
        CREATE TABLE `tags_in_posts` (
            `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `tags_id`   INTEGER NOT NULL,
            `posts_id`  INTEGER NOT NULL,
            FOREIGN KEY(`tags_id`) REFERENCES tags(id),
            FOREIGN KEY(`posts_id`) REFERENCES posts(id)
        );
EOT
);

    $pdo->commit();

    return $pdo;
}
