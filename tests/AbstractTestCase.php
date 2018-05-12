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

        return $db;
    }

    protected function createMysqlDatabase(array $scheme)
    {
        $db = new Database(new PDO('mysql:host=127.0.0.1;charset=utf8', 'root', ''));

        $db->executeTransaction(function ($db) use ($scheme) {
            foreach ($scheme as $command) {
                $db->execute($command);
            }
        });

        return $db;
    }
}
