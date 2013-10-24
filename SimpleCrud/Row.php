<?php
/**
 * SimpleCrud\Row
 * 
 * Stores the data of an entity row
 */
namespace SimpleCrud;

use SimpleCrud\Entity;

class Row implements HasEntityInterface, \JsonSerializable {
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


	/**
	 * Magic method to check if a property is defined or is empty
	 * 
	 * @param string $name Property name
	 *  
	 * @return boolean
	 */
	public function __isset ($name) {
		$value = $this->$name;

		return empty($value);
	}


	/**
	 * jsonSerialize interface
	 * 
	 * @return array
	 */
	public function jsonSerialize () {
		return $this->toArray();
	}


	/**
	 * Returns the entity of the row
	 * 
	 * @return SimpleCrud\Entity
	 */
	public function entity () {
		return $this->__entity;
	}


	/**
	 * Returns the entity manager
	 * 
	 * @return SimpleCrud\Manager
	 */
	public function manager () {
		return $this->__entity->getManager();
	}


	/**
	 * Returns true if is a collection, false if isn't
	 * 
	 * @return boolean
	 */
	public function isCollection () {
		return false;
	}


	/**
	 * Relate 'has-one' elements with this row
	 * 
	 * @param HasEntityInterface $row The row to relate
	 *
	 * @return $this
	 */
	public function setRelation (HasEntityInterface $row) {
		if (func_num_args() > 1) {
			foreach (func_get_args() as $row) {
				$this->setRelation($row);
			}

			return $this;
		}

		$entity = $row->entity();

		if ($this->entity()->getRelation($entity) !== Entity::RELATION_HAS_ONE) {
			throw new Exception("Not valid relation");
		}

		$foreignKey = $entity->foreignKey;
		$this->$foreignKey = $row->id;

		return $this;
	}


	/**
	 * Generate an array with all values and subvalues of the row
	 * 
	 * @param array $parentEntities Parent entities of this row. It's used to avoid infinite recursions
	 * 
	 * @return array
	 */
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


	/**
	 * Set new values to the row.
	 * 
	 * @param array $data The new values
	 * @param boolean $onlyDeclaredFields Set true to only set declared fields
	 *
	 * @return $this
	 */
	public function set (array $data, $onlyDeclaredFields = false) {
		if ($onlyDeclaredFields === true) {
			$data = array_intersect_key($data, $this->entity()->getFields());
		}

		foreach ($data as $name => $value) {
			$this->$name = $value;
		}

		return $this;
	}


	/**
	 * Return one or all values of the row
	 * 
	 * @param string $name The value name to recover. If it's not defined, returns all values. If it's true, returns only the fields values.
	 * 
	 * @return mixed The value or an array with all values
	 */
	public function get ($name = null) {
		$data = call_user_func('get_object_vars', $this);

		if ($name === true) {
			return array_intersect_key($data, $this->entity()->getFields());
		}

		if ($name === null) {
			return $data;
		}

		return isset($data[$name]) ? $data[$name] : null;
	}


	/**
	 * Saves this row in the database
	 * 
	 * @param boolean $duplicateKey Set true to detect duplicates index
	 * 
	 * @return $this
	 */
	public function save ($duplicateKey = false) {
		$data = $this->get(true);

		if (empty($this->id)) {
			$data = $this->entity()->insert($data, $duplicateKey);
		} else {
			$data = $this->entity()->update($data, 'id = :id', [':id' => $this->id], 1);
		}

		return $this->set($data);
	}


	/**
	 * Deletes the row in the database
	 * 
	 * @return $this
	 */
	public function delete () {
		if (empty($this->id)) {
			return false;
		}

		$this->entity()->delete('id = :id', [':id' => $this->id], 1);

		return $this;
	}


	/**
	 * Load related elements from the database
	 * 
	 * @param array $entities The entities names
	 * 
	 * @return $this
	 */
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
