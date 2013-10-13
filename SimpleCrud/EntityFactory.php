<?php
/**
 * SimpleCrud\EntityFactory
 *
 * Manages a create SimpleCrud\Entity instances
 */

namespace SimpleCrud;

class EntityFactory {
	protected $manager;
	protected $namespace;
	protected $autocreate;

	public function __construct (array $config = null) {
		$this->namespace = isset($config['namespace']) ? $config['namespace'] : '';

		if ($this->namespace && (substr($this->namespace, -1) !== '\\')) {
			$this->namespace .= '\\';
		}

		$this->autocreate = isset($config['autocreate']) ? (bool)$config['autocreate'] : false;
	}

	public function setManager (Manager $manager) {
		$this->manager = $manager;
	}

	public function create ($name) {
		$class = $this->namespace.ucfirst($name);

		if (!class_exists($class)) {
			if (!$this->autocreate) {
				return false;
			}

			$class = 'SimpleCrud\\Entity';
		}

		$entity = new $class($this->manager, $name);

		$table = empty($entity->table) ? $name : $entity->table;
		$fields = empty($entity->fields) ? $this->manager->execute("DESCRIBE `$table`")->fetchAll(\PDO::FETCH_COLUMN, 0) : $entity->fields;
		$foreignKey = empty($entity->foreignKey) ? "{$table}_id" : $entity->foreignKey;
		$rowClass = class_exists("{$class}Row") ? "{$class}Row" : 'SimpleCrud\\Row';
		$rowCollectionClass = class_exists("{$class}RowCollection") ? "{$class}RowCollection" : 'SimpleCrud\\RowCollection';

		$entity->setConfig($table, $fields, $foreignKey, $rowClass, $rowCollectionClass);

		$entity->onInit();

		return $entity;
	}
}
