<?php
use SimpleCrud\Adapters\Sqlite;
use SimpleCrud\Adapters\AdapterInterface;
use SimpleCrud\EntityFactory;

class SqliteAutocreateTest extends MysqlAutocreateTest
{
    public function testConnection()
    {
        $db = new Sqlite(initSqlitePdo(), new EntityFactory([
            'autocreate' => true,
        ]));

        $this->assertInstanceOf('SimpleCrud\\Adapters\\AdapterInterface', $db);

        return $db;
    }
}
