<?php
/**
 * SimpleCrud\Row
 * 
 * Stores the data of an entity row
 */
namespace SimpleCrud;

use SimpleCrud\Entity;

class Row implements HasEntityInterface, \JsonSerializable {
	private $values;
	private $changes = [];

	public $entity;
	public $manager;

	public function __construct (Entity $entity) {
		$this->entity = $entity;
		$this->manager = $entity->manager;
		$this->values = $entity->getDefaults();
	}


	/**
	 * Magic method to execute 'get' functions and save the result in a property.
	 *
	 * @param string $name The property name
	 */
	public function __get ($name) {
		$method = "get$name";

		if (array_key_exists($name, $this->values)) {
			return $this->values[$name];
		}

		if (method_exists($this, $method)) {
			return $this->values[$name] = $this->$method();
		}

		if ($this->entity->isRelated($name)) {
			$this->load([$name]);

			return $this->values[$name];
		}

		return null;
	}


	/**
	 * Magic method to execute 'set' function
	 * 
	 * @param string $name The property name
	 * @param mixed $value The value
	 */
	public function __set ($name, $value) {
		$this->changes[$name] = true;
		$this->values[$name] = $value;
	}


	/**
	 * Magic method to check if a property is defined or is empty
	 * 
	 * @param string $name Property name
	 *  
	 * @return boolean
	 */
	public function __isset ($name) {
		return !empty($this->values[$name]);
	}


	/**
	 * Magic method to print the row values (and subvalues)
	 * 
	 * @return string
	 */
	public function __toString () {
		return "\n".$this->entity->name.":\n".print_r($this->toArray(), true)."\n";
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
	 * Returns true if is a collection, false if isn't
	 * 
	 * @return boolean
	 */
	public function isCollection () {
		return false;
	}


	/**
	 * Empty the changes array
	 */
	public function emptyChanges () {
		$this->changes = [];

		return $this;
	}


	/**
	 * Return if the row values has been changed or not
	 */
	public function changed () {
		return !empty($this->changes);
	}


	/**
	 * Reload the row from the database
	 */
	public function reload () {
		if (!$this->id || !($row = $this->entity->selectBy($this->id))) {
			throw new \Exception("This row does not exist in database");
		}

		$this->set($row->get());
		$this->emptyChanges();
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

		if ($this->entity->getRelation($row->entity) !== Entity::RELATION_HAS_ONE) {
			throw new \Exception("Not valid relation");
		}

		if (empty($row->id)) {
			throw new \Exception('Rows without id value cannot be related');
		}

		$foreignKey = $row->entity->foreignKey;
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
		if ($parentEntities && in_array($this->entity->name, $parentEntities)) {
			return null;
		}

		$parentEntities[] = $this->entity->name;
		$data = $this->values;

		foreach ($data as &$value) {
			if ($value instanceof HasEntityInterface) {
				$value = $value->toArray($parentEntities);
			}
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
			$data = array_intersect_key($data, $this->entity->getFieldsNames());
		}

		foreach ($data as $name => $value) {
			$this->changes[$name] = true;
			$this->values[$name] = $value;
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
	public function get ($name = null, $onlyChangedValues = false) {
		$values = ($onlyChangedValues === true) ? array_intersect_key($this->values, $this->changes) : $this->values;

		if ($name === true) {
			return array_intersect_key($values, $this->entity->getFieldsNames());
		}

		if ($name === null) {
			return $values;
		}

		return isset($values[$name]) ? $values[$name] : null;
	}


	/**
	 * Saves this row in the database
	 * 
	 * @param boolean $duplicateKey Set true to detect duplicates index
	 * @param boolean $onlyChangedValues Set false to save all values instead only the changed
	 * 
	 * @return $this
	 */
	public function save ($duplicateKey = false, $onlyChangedValues = true) {
		$data = $this->get(true);
		$onlyChangedValues = $onlyChangedValues ? $this->changes : null;

		if (empty($this->id)) {
			$data = $this->entity->insert($data, $duplicateKey, $onlyChangedValues);
		} else {
			$data = $this->entity->update($data, 'id = :id', [':id' => $this->id], 1, $onlyChangedValues);
		}

		$this->set($data);
		$this->changes = [];

		return $this;
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

		$this->entity->delete('id = :id', [':id' => $this->id], 1);

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
		foreach ($entities as $name => $options) {
			if (!is_array($options)) {
				$this->$options = $this->manager->$options->selectBy($this);

				continue;
			}

			$this->$name = $this->manager->$name->selectBy($this,
				isset($options['join']) ? $options['join'] : null,
				isset($options['where']) ? $options['where'] : '',
				isset($options['marks']) ? $options['marks'] : null,
				isset($options['orderBy']) ? $options['orderBy'] : null,
				isset($options['limit']) ? $options['limit'] : null
			);
		}

		return $this;
	}
}
