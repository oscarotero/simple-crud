<?php
error_reporting(E_ALL);

include_once dirname(__DIR__).'/src/autoloader.php';
include_once __DIR__.'/entities.php';

PHPUnit_Framework_Error_Notice::$enabled = true;

function initMysqlPdo()
{
    //create de database
    $pdo = new PDO('mysql:host=localhost;charset=UTF8', 'root', '');

    $pdo->exec('DROP DATABASE IF EXISTS simplecrud_test');
    $pdo->exec('CREATE DATABASE simplecrud_test DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci');
    $pdo->exec('USE simplecrud_test');


    $pdo->beginTransaction();

    //Posts
	$pdo->exec(<<<EOT
		CREATE TABLE `posts` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`title` varchar(255) COLLATE utf8_unicode_ci NULL,
			`categories_id` int(11) unsigned DEFAULT NULL,
			`pubdate` datetime DEFAULT NULL,
			`day` date DEFAULT NULL,
			`time` time DEFAULT NULL,
			`type` set('text','image','video','audio') DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `categories_id` (`categories_id`)
		)
		ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
EOT
);

	//Categories
	$pdo->exec(<<<EOT
		CREATE TABLE `categories` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`id`),
			KEY `name` (`name`)
		)
		ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
EOT
);

	//Tags
	$pdo->exec(<<<EOT
		CREATE TABLE `tags` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`id`)
		)
		ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
EOT
);

	//Relationship Tags-Posts
    $pdo->exec(<<<EOT
		CREATE TABLE `tags_in_posts` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`posts_id` int(11) unsigned NOT NULL,
			`tags_id` int(11) unsigned NOT NULL,
			PRIMARY KEY (`id`),
			KEY `posts_id` (`posts_id`),
			KEY `tags_id` (`tags_id`)
		)
		ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
EOT
);


    //Foreign keys for Posts
	$pdo->exec(<<<EOT
		ALTER TABLE `posts`
		ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
EOT
);

	//Foreign keys for Tags-Posts
	$pdo->exec(<<<EOT
		ALTER TABLE `tags_in_posts`
		ADD CONSTRAINT `tags_in_posts_ibfk_2` FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE,
		ADD CONSTRAINT `tags_in_posts_ibfk_1` FOREIGN KEY (`posts_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;
EOT
);
    $pdo->commit();

    return $pdo;
}


function initSqlitePdo()
{
	$pdo = new PDO('sqlite::memory:');
	
	$pdo->beginTransaction();

	//Posts
	$pdo->exec(<<<EOT
		CREATE TABLE "posts" (
			`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
			`title`	TEXT,
			`categories_id`	INTEGER,
			`pubdate`	TEXT,
			`day`	TEXT,
			`time`	TEXT,
			`type`	TEXT,
			FOREIGN KEY(`categories_id`) REFERENCES categories(id)
		);
EOT
);

	//Categories
	$pdo->exec(<<<EOT
		CREATE TABLE `categories` (
			`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
			`name`	TEXT
		);
EOT
);

	//Tags
	$pdo->exec(<<<EOT
		CREATE TABLE `tags` (
			`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
			`name`	TEXT
		);
EOT
);
	
	//Relationship Tags-Posts
	$pdo->exec(<<<EOT
		CREATE TABLE `tags_in_posts` (
			`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
			`tags_id`	INTEGER NOT NULL,
			`posts_id`	INTEGER NOT NULL,
			FOREIGN KEY(`tags_id`) REFERENCES tags(id),
			FOREIGN KEY(`posts_id`) REFERENCES posts(id)
		);
EOT
);

	$pdo->commit();

	return $pdo;
}
