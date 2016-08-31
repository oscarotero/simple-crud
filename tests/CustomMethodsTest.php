<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

class CustomPropertiesTest extends PHPUnit_Framework_TestCase
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
    `title`       TEXT,
    `isActive`    INTEGER
);
EOT
            );
        });
    }

    public function testCustomMethods()
    {
        $db = $this->db;

        $db->post->setRowMethod('upperCaseTitle', function () {
            return strtoupper($this->title);
        });

        $post = $db->post->create([
            'title' => 'First post',
            'isActive' => true,
        ])->save();

        $post2 = $db->post->create([
            'title' => 'Second post',
            'isActive' => true,
        ])->save();

        $this->assertEquals('FIRST POST', $post->upperCaseTitle());

        $post->setMethod('upperCaseTitle', function () {
            return 'overrided!';
        });

        $this->assertEquals('overrided!', $post->upperCaseTitle());
        $this->assertEquals('SECOND POST', $post2->upperCaseTitle());
    }
}
