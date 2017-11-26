<?php

use SimpleCrud\SimpleCrud;

class AutocreateTest extends PHPUnit_Framework_TestCase
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
    `category_id` INTEGER,
    `publishedAt` TEXT,
    `isActive`    INTEGER,
    `hasContent`  INTEGER,
    `type`        TEXT,
    `rating`      REAL
);
EOT
            );
        });
    }

    public function testDatabase()
    {
        $this->assertInstanceOf('SimpleCrud\\TableFactory', $this->db->getTableFactory());
        $this->assertInstanceOf('SimpleCrud\\FieldFactory', $this->db->getFieldFactory());
        $this->assertInstanceOf('SimpleCrud\\QueryFactory', $this->db->getQueryFactory());
        $this->assertInternalType('array', $this->db->getScheme());

        $this->db->setAttribute('bar', 'foo');

        $this->assertEquals('sqlite', $this->db->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertEquals('foo', $this->db->getAttribute('bar'));
    }

    public function testTable()
    {
        $this->assertTrue(isset($this->db->post));
        $this->assertFalse(isset($this->db->invalid));

        $post = $this->db->post;

        $this->assertInstanceOf('SimpleCrud\\Table', $post);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $post->getDatabase());

        $this->assertCount(8, $post->getScheme()['fields']);
        $this->assertEquals('post', $post->getName());
        $this->assertEquals($this->db->getScheme()['post'], $post->getScheme());
    }

    public function dataProviderFields()
    {
        return [
            ['id', 'Integer'],
            ['title', 'Field'],
            ['category_id', 'Integer'],
            ['publishedAt', 'Datetime'],
            ['isActive', 'Boolean'],
            ['hasContent', 'Boolean'],
            ['type', 'Field'],
            ['rating', 'Decimal'],
        ];
    }

    /**
     * @dataProvider dataProviderFields
     */
    public function testFields($name, $type)
    {
        $post = $this->db->post;
        $field = $post->$name;

        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $field);
        $this->assertInstanceOf('SimpleCrud\\Fields\\'.$type, $field);

        $this->assertEquals($this->db->post->getScheme()['fields'][$name], $field->getScheme());
    }

    public function testOnExecuteQuery()
    {
        $log = [];
        $queries = [
            'SELECT name FROM sqlite_master WHERE (type="table" OR type="view") AND name != "sqlite_sequence"',
            'pragma table_info(`post`)',
        ];

        $this->db->onExecute(function ($pdo, $statement, $marks) use (&$log) {
            $this->assertInstanceOf('PDO', $pdo);
            $this->assertInstanceOf('PDOStatement', $statement);

            $log[] = $statement->queryString;
        });

        $post = $this->db->post;

        $this->assertEquals($log, $queries);
    }
}
