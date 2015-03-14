<?php
use SimpleCrud\Adapters\Sqlite;
use SimpleCrud\Adapters\AdapterInterface;
use SimpleCrud\EntityFactory;

class SqliteEntitiesTest extends MysqlEntitiesTest
{
    public function testConnection()
    {
        $db = new Sqlite(initSqlitePdo(), new EntityFactory([
            'autocreate' => true,
            'namespace' => 'Custom\\',
        ]));

        $this->assertInstanceOf('SimpleCrud\\Adapters\\AdapterInterface', $db);

        return $db;
    }
}
