<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

class SchemeTest extends PHPUnit_Framework_TestCase
{
    private $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(new PDO('sqlite::memory:'));

        $this->db->executeTransaction(function ($db) {
            $db->execute(
<<<EOT
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT
);
EOT
            );

            $db->execute(
<<<EOT
CREATE TABLE "category" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`        TEXT
);
EOT
            );

            $db->execute(
<<<EOT
CREATE TABLE "comment" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `text`        TEXT,
    `post_id`     INTEGER
);
EOT
            );

            $db->execute(
<<<EOT
CREATE TABLE "category_post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `category_id` INTEGER NOT NULL,
    `post_id`     INTEGER NOT NULL
);
EOT
            );
        });
    }

    public function testScheme()
    {
        $db = $this->db;

        $scheme = [
            'post' => [
                'fields' => [
                    'id' => [
                        'type' => 'integer',
                        'null' => false,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                    'title' => [
                        'type' => 'text',
                        'null' => true,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                ],
                'relations' => [
                    'category' => [4, 'category_post', 'post_id', 'category_id'],
                    'comment' => [2, 'post_id'],
                    'category_post' => [2, 'post_id'],
                ],
            ],
            'category' => [
                'fields' => [
                    'id' => [
                        'type' => 'integer',
                        'null' => false,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                    'name' => [
                        'type' => 'text',
                        'null' => true,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                ],
                'relations' => [
                    'post' => [4, 'category_post', 'category_id', 'post_id'],
                    'category_post' => [2, 'category_id'],
                ],
            ],
            'comment' => [
                'fields' => [
                    'id' => [
                        'type' => 'integer',
                        'null' => false,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                    'text' => [
                        'type' => 'text',
                        'null' => true,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                    'post_id' => [
                        'type' => 'integer',
                        'null' => true,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                ],
                'relations' => [
                    'post' => [1, 'post_id'],
                ],
            ],
            'category_post' => [
                'fields' => [
                    'id' => [
                        'type' => 'integer',
                        'null' => false,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                    'category_id' => [
                        'type' => 'integer',
                        'null' => false,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                    'post_id' => [
                        'type' => 'integer',
                        'null' => false,
                        'default' => null,
                        'unsigned' => false,
                        'length' => null,
                        'values' => null,
                    ],
                ],
                'relations' => [
                    'post' => [1, 'post_id'],
                    'category' => [1, 'category_id'],
                ],
            ],
        ];

        $this->assertEquals($scheme, $db->getScheme());
    }
}
