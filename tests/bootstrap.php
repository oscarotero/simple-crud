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
        CREATE TABLE "post" (
            `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `title`       TEXT,
            `category_id` INTEGER,
            `publishedAt` TEXT,
            `isActive`    INTEGER,
            `type`        TEXT,
            FOREIGN KEY(`category_id`) REFERENCES category(id)
        );
EOT
);

    //Categories
    $pdo->exec(<<<EOT
        CREATE TABLE `category` (
            `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `name`  TEXT
        );
EOT
);

    //Tags
    $pdo->exec(<<<EOT
        CREATE TABLE `tag` (
            `id`    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `name`  TEXT
        );
EOT
);

    //Relationship Tags-Posts
    $pdo->exec(<<<EOT
        CREATE TABLE `post_tag` (
            `id`      INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `tag_id`  INTEGER NOT NULL,
            `post_id` INTEGER NOT NULL,
            FOREIGN KEY(`tag_id`) REFERENCES tag(id),
            FOREIGN KEY(`post_id`) REFERENCES post(id)
        );
EOT
);
    //View
    $pdo->exec(<<<EOT
        CREATE VIEW `tagsCounter` AS
            SELECT `tag_id`, count(*) `total`
            FROM `post_tag`
            GROUP BY `tag_id`
EOT
);

    $pdo->commit();

    return $pdo;
}
