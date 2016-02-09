<?php

namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql\Update as BaseUpdate;

/**
 * Manages a database update query in Sqlite databases.
 */
class Update extends BaseUpdate
{
    use UpdateDeleteTrait;
}
