<?php

namespace SimpleCrud\Tests;

use Latitude\QueryBuilder\QueryFactory;
use PDO;
use SimpleCrud\Database;
use SimpleCrud\FieldFactory;
use SimpleCrud\Fields\Field;
use SimpleCrud\SchemeInterface;
use SimpleCrud\Table;

class AutocreateTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
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
        ]);
    }

    public function testDatabase(): Database
    {
        $db = $this->createDatabase();

        $this->assertInstanceOf(FieldFactory::class, $db->getFieldFactory());
        $this->assertInstanceOf(QueryFactory::class, $db->query());
        $this->assertInstanceOf(SchemeInterface::class, $db->getScheme());

        $db->setAttribute('bar', 'foo');

        $this->assertEquals('sqlite', $db->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertEquals('foo', $db->getAttribute('bar'));

        return $db;
    }

    /**
     * @depends testDatabase
     */
    public function testTable(Database $db)
    {
        $this->assertTrue(isset($db->post));
        $this->assertFalse(isset($db->invalid));

        $post = $db->post;

        $this->assertInstanceOf(Table::class, $post);
        $this->assertInstanceOf(Database::class, $post->getDatabase());

        $this->assertCount(8, $post->getFields());
        $this->assertEquals('post', $post->getName());
    }

    public function dataProviderFields(): array
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
     * @depends testDatabase
     */
    public function testFields(string $name, string $type, Database $db)
    {
        $post = $db->post;
        $field = $post->$name;

        $this->assertInstanceOf(Field::class, $field);
        $this->assertInstanceOf('SimpleCrud\\Fields\\'.$type, $field);

        $this->assertEquals($name, $field->getName());
    }
}
