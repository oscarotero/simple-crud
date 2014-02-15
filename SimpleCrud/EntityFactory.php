<?php
/**
 * SimpleCrud\EntityFactory
 *
 * Manages a create SimpleCrud\Entity instances
 */

namespace SimpleCrud;


class EntityFactory {
	protected $entityNamespace;
	protected $fieldsNamespace;
	protected $autocreate;

	public function __construct (array $config = null) {
		$this->entityNamespace = isset($config['namespace']) ? $config['namespace'] : '';

		if ($this->entityNamespace && (substr($this->entityNamespace, -1) !== '\\')) {
			$this->entityNamespace .= '\\';
		}

		$this->fieldsNamespace = $this->entityNamespace.'Fields\\';
		$this->autocreate = isset($config['autocreate']) ? (bool)$config['autocreate'] : false;
	}

	
	/**
	 * Creates a new instance of an Entity
	 *
	 * @param SimpleCrud\Manager $manager The manager related with this entity
	 * @param string $name The name of the entity
	 *
	 * @return SimpleCrud\Entity The created entity
	 */
	public function create (Manager $manager, $name) {
		$class = $this->entityNamespace.ucfirst($name);

		if (!class_exists($class)) {
			if (!$this->autocreate || !in_array($name, $this->getTables($manager))) {
				return false;
			}

			$class = 'SimpleCrud\\Entity';
		}

		$entity = new $class($manager, $name);

		//Configure the entity
		if (empty($entity->table)) {
			$entity->table = $name;
		}

		if (empty($entity->foreignKey)) {
			$entity->foreignKey = "{$entity->table}_id";
		}

		$entity->rowClass = class_exists("{$class}Row") ? "{$class}Row" : 'SimpleCrud\\Row';
		$entity->rowCollectionClass = class_exists("{$class}RowCollection") ? "{$class}RowCollection" : 'SimpleCrud\\RowCollection';

		//Define fields
		$fields = [];

		if (empty($entity->fields)) {
			foreach ($this->getFields($manager, $entity->table) as $name => $type) {
				$fields[$name] = $this->createField($entity, $name, $type);
			}
		} else {
			foreach ($entity->fields as $name => $type) {
				if (is_int($name)) {
					$fields[$type] = $this->createField($entity, $type, 'field');
				} else {
					$fields[$name] = $this->createField($entity, $name, $type);
				}
			}
		}

		$entity->fields = $fields;

		//Init callback
		$entity->init();

		return $entity;
	}


	/**
	 * Creates a field instance
	 *
	 * @param SimpleCrud\Entity $entity The entity of the field
	 * @param string $name The field name
	 * @param string $type The field type
	 *
	 * @return SimpleCrud\Fieds\Field The created field
	 */
	private function createField (Entity $entity, $name, $type) {
		$class = $this->fieldsNamespace.ucfirst($type);

		if (!class_exists($class)) {
			$class = 'SimpleCrud\\Fields\\'.ucfirst($type);

			if (!class_exists($class)) {
				$class = 'SimpleCrud\\Fields\\Field';
			}
		}

		return new $class($entity, $name);
	}


	/**
	 * Returns a list of all fields in a table
	 *
	 * @param SimpleCrud\Manager $manager The database manager
	 * @param string $table The table name
	 *
	 * @return array The fields [name => type]
	 */
	private function getFields (Manager $manager, $table) {
		$fields = [];

		foreach ($manager->execute("DESCRIBE `$table`")->fetchAll() as $field) {
			preg_match('#^(\w+)#', $field['Type'], $matches);

			$fields[$field['Field']] = $matches[1];
		}

		return $fields;
	}


	/**
	 * Returns all tables of the database
	 *
	 * @param SimpleCrud\Manager $manager The database manager
	 *
	 * @return array The table names
	 */
	private function getTables (Manager $manager) {
		static $tables;

		if ($tables !== null) {
			return $tables;
		}

		return $tables = $manager->execute("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN, 0);
	}
}
