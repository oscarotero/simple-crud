<?php

namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql\Delete as BaseDelete;

/**
 * Manages a database delete query in Sqlite databases.
 */
class Delete extends BaseDelete
{
    use UpdateDeleteTrait;
}
