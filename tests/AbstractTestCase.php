<?php

namespace SimpleCrud\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use SimpleCrud\Database;

abstract class AbstractTestCase extends TestCase
{
    protected function createSqliteDatabase(array $scheme)
    {
        $db = new Database(new PDO('sqlite::memory:'));

        $db->executeTransaction(function ($db) use ($scheme) {
            foreach ($scheme as $command) {
                $db->execute($command);
            }
        });

        $db->getConnection()->logQueries(true);

        return $db;
    }

    protected function createMysqlDatabase(array $scheme)
    {
        $pass = getenv('MYSQL_PASS') ?: '';
        $db = new Database(new PDO('mysql:host=127.0.0.1;charset=utf8', 'root', $pass));

        $db->executeTransaction(function ($db) use ($scheme) {
            foreach ($scheme as $command) {
                $db->execute($command);
            }
        });

        $db->getConnection()->logQueries(true);

        return $db;
    }

    protected function assertQuery(Database $db, array $values, string $statement)
    {
        $queries = $db->getConnection()->getQueries();
        $query = array_pop($queries);

        $this->assertSame($statement, $query['statement']);
        $this->assertEquals($values, array_values($query['values']));
    }
}
