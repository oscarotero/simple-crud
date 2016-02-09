<?php

use SimpleCrud\SimpleCrud;

class AutocreateTest extends PHPUnit_Framework_TestCase
{
    static private $db;

    static public function setUpBeforeClass()
    {
        self::$db = new SimpleCrud(new PDO('sqlite::memory:'));
        
        self::$db->executeTransaction(function ($db) {
            $db->execute(
<<<EOT
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `category_id` INTEGER,
    `publishedAt` TEXT,
    `isActive`    INTEGER,
    `inHome`      INTEGER,
    `hasContent`  INTEGER,
    `type`        TEXT
);
EOT
            );
        });
    }

    public function testDatabase()
    {
        $this->assertInstanceOf('SimpleCrud\\TableFactory', self::$db->getTableFactory());
        $this->assertInstanceOf('SimpleCrud\\FieldFactory', self::$db->getFieldFactory());
        $this->assertInstanceOf('SimpleCrud\\QueryFactory', self::$db->getQueryFactory());
        $this->assertInternalType('array', self::$db->getScheme());

        self::$db->setAttribute('bar', 'foo');

        $this->assertEquals('sqlite', self::$db->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertEquals('foo', self::$db->getAttribute('bar'));
    }

    public function testTable()
    {
        $this->assertTrue(isset(self::$db->post));
        $this->assertFalse(isset(self::$db->invalid));

        $post = self::$db->post;

        $this->assertInstanceOf('SimpleCrud\\Table', $post);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $post->getDatabase());

        $this->assertCount(8, $post->fields);
        $this->assertEquals('post', $post->name);
        $this->assertEquals('post_id', $post->foreignKey);
        $this->assertEquals(self::$db->getScheme()['post'], $post->getScheme());
    }

    public function dataProviderFields()
    {
        return [
            ['id', 'Integer'],
            ['title', 'Field'],
            ['category_id', 'Integer'],
            ['publishedAt', 'Datetime'],
            ['isActive', 'Boolean'],
            ['inHome', 'Boolean'],
            ['hasContent', 'Boolean'],
            ['type', 'Field'],
        ];
    }

    /**
     * @dataProvider dataProviderFields
     */
    public function testFields($name, $type)
    {
        $post = self::$db->post;
        $field = $post->fields[$name];

        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $field);
        $this->assertInstanceOf('SimpleCrud\\Fields\\'.$type, $field);

        $this->assertEquals(self::$db->post->getScheme()[$name], $field->getScheme());
    }

    public function dataProviderQueries()
    {
        return [
            ['count'],
            ['delete'],
            ['insert'],
            ['select'],
            ['sum'],
            ['update'],
        ];
    }

    /**
     * @dataProvider dataProviderQueries
     */
    public function testQueries($name)
    {
        $query = self::$db->post->$name();

        $this->assertInstanceOf('SimpleCrud\\Queries\\Sqlite\\'.ucfirst($name), $query);
        $this->assertInstanceOf('SimpleCrud\\Queries\\Query', $query);
    }
}
