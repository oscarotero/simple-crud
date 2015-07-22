<?php
namespace SimpleCrud\Adapters\Mysql;

use SimpleCrud\Adapters\Adapter;

/**
 * Adapter class for Mysql databases
 */
class Mysql extends Adapter implements AdapterInterface
{
	protected $type = 'Mysql';

    /**
     * {@inheritdoc}
     */
    public function getTables()
    {
        return $this->execute('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
    	return $this->type;
    }
}
