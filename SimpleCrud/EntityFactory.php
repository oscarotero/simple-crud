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
		$this->autocreate = isset($config['autocreate']) ? (bool)$config['autocreate'] : false;
	}

	public function setManager (Manager $manager) {
		$this->manager = $manager;
	}

	public function create ($name) {
		$class = $this->namespace.ucfirst($name);

		if (!class_exists($class)) {
			if (!$this->autocreate) {
				throw new \Exception("Entity '$name' is not defined");
			}

			$class = 'SimpleCrud\\Entity';
		}

		$entity = new $class($this->manager);

		$table = empty($entity->table) ? $name : $entity->table;
		$fields = empty($entity->fields) ? $this->manager->execute("DESCRIBE `$table`")->fetchAll(\PDO::FETCH_COLUMN, 0) : $entity->fields;
		$foreignKey = empty($entity->foreignKey) ? "{$table}_id" : $entity->foreignKey;

		$entity->setConfig($table, $fields, $foreignKey);

		return $entity;
	}
}
