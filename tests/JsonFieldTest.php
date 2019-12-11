<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Fields\Json;

class JsonFieldTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createMysqlDatabase([
            'DROP DATABASE IF EXISTS `simple_crud`',
            'CREATE DATABASE `simple_crud`',
            'USE `simple_crud`',
            <<<'SQL'
CREATE TABLE `post` (
    `id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
    `info` json DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL
        ]);
    }

    public function testFields()
    {
        $db = $this->createDatabase();

        $this->assertInstanceOf(Json::class, $db->post->info);
        $this->assertCount(0, $db->post);

        $db->post[] = [];

        $this->assertCount(1, $db->post);
        $post = $db->post[1];

        $this->assertNull($post->info);

        $array = $db->post->select()->one()->getArray();

        print_r($array);
    }
}
