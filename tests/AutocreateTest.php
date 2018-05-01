<?php

namespace SimpleCrud\Tests;

use Latitude\QueryBuilder\QueryFactory;
use SimpleCrud\Table;
use SimpleCrud\SimpleCrud;
use SimpleCrud\TableFactory;
use SimpleCrud\FieldFactory;
use SimpleCrud\Engine\SchemeInterface;
use PDO;

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

    public function testDatabase(): SimpleCrud
    {
        $db = $this->createDatabase();

        $this->assertInstanceOf(TableFactory::class, $db->getTableFactory());
        $this->assertInstanceOf(FieldFactory::class, $db->getFieldFactory());
        $this->assertInstanceOf(QueryFactory::class, $db->query());
        $this->assertInstanceOf(SchemeInterface::class, $db->getScheme());
        $this->assertInternalType('array', $db->getScheme()->toArray());

        $db->setAttribute('bar', 'foo');

        $this->assertEquals('sqlite', $db->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertEquals('foo', $db->getAttribute('bar'));

        return $db;
    }

    /**
     * @depends testDatabase
     */
    public function testTable(SimpleCrud $db)
    {
        $this->assertTrue(isset($db->post));
        $this->assertFalse(isset($db->invalid));

        $post = $db->post;

        $this->assertInstanceOf(Table::class, $post);
        $this->assertInstanceOf(SimpleCrud::class, $post->getDatabase());

        $this->assertCount(8, $post->getScheme()['fields']);
        $this->assertEquals('post', $post->getName());
        $this->assertEquals($db->getScheme()->toArray()['post'], $post->getScheme());
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
    public function testFields(string $name, string $type, SimpleCrud $db)
    {
        $post = $db->post;
        $field = $post->$name;

        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $field);
        $this->assertInstanceOf('SimpleCrud\\Fields\\'.$type, $field);

        $this->assertEquals($db->post->getScheme()['fields'][$name], $field->getScheme());
    }
}
