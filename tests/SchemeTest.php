<?php

namespace SimpleCrud\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use SimpleCrud\Database;
use SimpleCrud\Table;

class SchemeTest extends TestCase
{
    private $db;

    public function setUp()
    {
        $this->db = new Database(new PDO('sqlite::memory:'));

        $this->db->executeTransaction(function ($db) {
            $db->execute(
<<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT
);
EOT
            );

            $db->execute(
<<<'EOT'
CREATE TABLE "category" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`        TEXT
);
EOT
            );

            $db->execute(
<<<'EOT'
CREATE TABLE "comment" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `text`        TEXT,
    `post_id`     INTEGER
);
EOT
            );

            $db->execute(
<<<'EOT'
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
        $scheme = $this->db->getScheme();

        $expected = [
            'post' => [
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'null' => false,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
                [
                    'name' => 'title',
                    'type' => 'text',
                    'null' => true,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
            ],
            'category' => [
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'null' => false,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
                [
                    'name' => 'name',
                    'type' => 'text',
                    'null' => true,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
            ],
            'comment' => [
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'null' => false,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
                [
                    'name' => 'text',
                    'type' => 'text',
                    'null' => true,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
                [
                    'name' => 'post_id',
                    'type' => 'integer',
                    'null' => true,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
            ],
            'category_post' => [
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'null' => false,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
                [
                    'name' => 'category_id',
                    'type' => 'integer',
                    'null' => false,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
                [
                    'name' => 'post_id',
                    'type' => 'integer',
                    'null' => false,
                    'default' => null,
                    'unsigned' => false,
                    'length' => null,
                    'values' => null,
                ],
            ],
        ];

        $this->assertEquals(array_keys($expected), $scheme->getTables());

        foreach ($expected as $table => $fields) {
            $this->assertEquals($fields, $scheme->getTableFields($table));
        }
    }
}
