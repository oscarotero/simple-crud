<?php
namespace SimpleCrud\Tests;

use Datetime;
use SimpleCrud\Fields\Json;
use SimpleCrud\Fields\Serializable;

class FieldsTest extends AbstractTestCase
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
    `title` varchar(100) DEFAULT '',
    `body`  text,
    `publishedAt` datetime DEFAULT NULL,
    `num`   decimal(10,0) DEFAULT NULL,
    `point` point DEFAULT NULL,
    `data` text DEFAULT NULL,
    `serializable` text,
    `isActive` boolean,
    `size`  enum('x-small', 'small', 'medium', 'large', 'x-large'),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL
        ]);
    }

    public function testFields()
    {
        $db = $this->createDatabase();
        $db->getFieldFactory(Json::class)->addNames('data');
        $db->getFieldFactory(Serializable::class)->addNames('serializable');

        $this->assertInstanceOf(Json::class, $db->post->data);

        $now = new Datetime();

        $db->post->insert([
            'title' => 'First post',
            'publishedAt' => $now,
            'point' => [100, 200],
            'num' => '34',
            'data' => [
                'foo' => 'bar',
                'bar' => [1, 2, 3],
            ],
            'serializable' => (object) [
                'foo' => 'bar',
                'bar' => 'foo',
            ],
        ])->run();

        $post = $db->post[1];

        $this->assertInstanceOf(Datetime::class, $post->publishedAt);
        $this->assertNotSame($now, $post->publishedAt);
        $this->assertEquals($now->format('Y-m-d h:i:s'), $post->publishedAt->format('Y-m-d h:i:s'));
        $this->assertIsInt($post->id);
        $this->assertIsArray($post->data);
        $this->assertIsArray($post->point);
        $this->assertIsBool($post->isActive);

        $this->assertFalse($post->isActive);
        $post->isActive = true;
        $this->assertTrue($post->isActive);
        $post->reload();
        $this->assertFalse($post->isActive);
    }
}
