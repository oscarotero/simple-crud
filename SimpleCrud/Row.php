<?php
/**
 * SimpleCrud\Row
 * 
 * Stores the data of an entity row
 */
namespace SimpleCrud;

use SimpleCrud\Entity;

class Row implements HasEntityInterface {
	private $__entity;

	public function __construct (Entity $entity) {
		$this->__entity = $entity;
		$this->set($entity->defaults);
	}


	/**
	 * Magic method to execute 'get' functions and save the result in a property.
	 *
	 * @param string $name The property name
	 */
	public function __get ($name) {
		$method = "get$name";

		if (method_exists($this, $method)) {
			return $this->$name = $this->$method();
		}

		if ($this->entity()->isRelated($name)) {
			$this->load([$name]);

			return $this->$name;
		}

		return null;
	}


	public function __isset ($name) {
		$value = $this->$name;

		return empty($value);
	}


	public function jsonSerialize () {
		return $this->toArray();
	}


	public function entity () {
		return $this->__entity;
	}


	public function manager () {
		return $this->__entity->getManager();
	}


	public function isCollection () {
		return false;
	}


	public function set (array $data) {
		foreach ($data as $name => $value) {
			$this->$name = $value;
		}

		return $this;
	}


	public function setRelation (HasEntityInterface $row) {
		if (func_num_args() > 1) {
			foreach (func_get_args() as $row) {
				$this->setRelation($row);
			}

			return $this;
		}

		$entity = $row->entity();

		if ($this->entity()->getRelation($entity) !== self::RELATION_HAS_ONE) {
			throw new Exception("Not valid relation");
		}

		$foreignKey = $entity->foreignKey;
		$this->$foreignKey = $row->id;

		return $this;
	}


	public function toArray (array $parentEntities = array()) {
		$name = $this->entity()->name;

		if ($parentEntities && in_array($name, $parentEntities)) {
			return null;
		}

		$parentEntities[] = $name;
		$data = [];

		foreach (call_user_func('get_object_vars', $this) as $k => $row) {
			if ($row instanceof HasEntityInterface) {
				if (($row = $row->toArray($parentEntities)) !== null) {
					$data[$k] = $row;
				}

				continue;
			}

			$data[$k] = $row;
		}

		return $data;
	}


	public function get ($name = null) {
		$data = call_user_func('get_object_vars', $this);

		if ($name === null) {
			return $data;
		}

		return isset($data[$name]) ? $data[$name] : null;
	}


	public function save () {
		$entity = $this->entity();
		$data = [];

		foreach ($entity->getFields() as $name) {
			$data[$name] = $this->$name;
		}

		if (empty($this->id)) {
			$data = $entity->insert($data);
		} else {
			$data = $entity->update($data, 'id = :id', [':id' => $this->id], 1);
		}

		return $this->set($data);
	}


	public function delete () {
		if (empty($this->id)) {
			return false;
		}

		$this->entity()->delete('id = :id', [':id' => $this->id], 1);

		return $this;
	}


	public function load (array $entities) {
		$entity = $this->entity();
		$manager = $this->manager();

		foreach ($entities as $name => $joins) {
			if (is_int($name)) {
				$name = $joins;
				$joins = null;
			}

			if (!$entity->isRelated($name)) {
				throw new \Exception("Cannot load '$name' because is not related or does not exists");
			}

			$this->$name = $manager->$name->selectBy($this, $joins);
		}

		return $this;
	}
}
