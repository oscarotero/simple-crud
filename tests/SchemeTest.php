<?php

namespace SimpleCrud\Tests;

use SimpleCrud\Scheme\Cache;

class SchemeTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT
);
EOT
            ,
            <<<'EOT'
CREATE TABLE "category" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `name`        TEXT
);
EOT
            ,
            <<<'EOT'
CREATE TABLE "comment" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `text`        TEXT,
    `post_id`     INTEGER
);
EOT
            ,
            <<<'EOT'
CREATE TABLE "category_post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `category_id` INTEGER NOT NULL,
    `post_id`     INTEGER NOT NULL
);
EOT
        ]);
    }

    public function testScheme()
    {
        $db = $this->createDatabase();
        $scheme = $db->getScheme();

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

        $array = Cache::schemeToArray($scheme);
        $this->assertEquals($expected, $array);

        $cacheScheme = new Cache($array);
        $this->assertEquals(array_keys($expected), $cacheScheme->getTables());

        foreach ($expected as $table => $fields) {
            $this->assertEquals($fields, $cacheScheme->getTableFields($table));
        }
    }
}
