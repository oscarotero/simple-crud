<?php
/**
 * SimpleCrud\RowCollection
 * 
 * Stores a row collection of an entity
 */
namespace SimpleCrud;

class RowCollection implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable, HasEntityInterface {
	private $rows = [];

	public $entity;
	public $manager;

	public function __construct (Entity $entity) {
		$this->entity = $entity;
		$this->manager = $entity->manager;
	}


	/**
	 * Magic method to execute the get method on access to undefined property
	 * 
	 * @see SimpleCrud\RowCollection::get()
	 */
	public function __get ($name) {
		return $this->get($name);
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
	 * Returns true if is a collection, false if isn't
	 * 
	 * @return boolean
	 */
	public function isCollection () {
		return true;
	}


	/**
	 * ArrayAcces interface
	 */
	public function offsetSet ($offset, $value) {
		if (is_null($offset)) {
			$offset = $value->id;
		}

		if (is_null($offset)) {
			$this->rows[] = $value;
		} else {
			$this->rows[$offset] = $value;
		}
	}


	/**
	 * ArrayAcces interface
	 */
	public function offsetExists ($offset) {
		return isset($this->rows[$offset]);
	}


	/**
	 * ArrayAcces interface
	 */
	public function offsetUnset ($offset) {
		unset($this->rows[$offset]);
	}


	/**
	 * ArrayAcces interface
	 */
	public function offsetGet ($offset) {
		return isset($this->rows[$offset]) ? $this->rows[$offset] : null;
	}


	/**
	 * Iterator interface
	 */
	public function rewind () {
		return reset($this->rows);
	}


	/**
	 * Iterator interface
	 */
	public function current () {
		return current($this->rows);
	}


	/**
	 * Iterator interface
	 */
	public function key () {
		return key($this->rows);
	}


	/**
	 * Iterator interface
	 */
	public function next () {
		return next($this->rows);
	}


	/**
	 * Iterator interface
	 */
	public function valid () {
		return key($this->rows) !== null;
	}


	/**
	 * Countable interface
	 */
	public function count () {
		return count($this->rows);
	}


	/**
	 * Magic method to execute the same function in all rows
	 * @param string $name The function name
	 * @param string $args Array with all arguments passed to the function
	 * 
	 * @return $this
	 */
	public function __call ($name, $args) {
		foreach ($this->rows as $row) {
			call_user_func_array([$row, $name], $args);
		}

		return $this;
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
	 * Generate an array with all values and subvalues of the collection
	 * 
	 * @param array $parentEntities Parent entities of this row. It's used to avoid infinite recursions
	 * 
	 * @return array
	 */
	public function toArray (array $parentEntities = array()) {
		if ($parentEntities && in_array($this->entity->name, $parentEntities)) {
			return null;
		}

		$rows = [];

		foreach ($this->rows as $row) {
			$rows[] = $row->toArray($parentEntities);
		}

		return $rows;
	}


	/**
	 * Returns one or all values of the collections
	 * 
	 * @param string $name The value name. If it's not defined returns all values
	 * @param string $key  The parameter name used for the keys. If it's not defined, returns a numerica array
	 * 
	 * @return array All values found. It generates a RowCollection if the values are rows.
	 */
	public function get ($name = null, $key = null) {
		if (is_int($name)) {
			if ($key === true) {
				return current(array_slice($this->rows, $name, 1));
			}

			return array_slice($this->rows, $name, $key, true);
		}

		$rows = [];

		if ($name === null) {
			if ($key === null) {
				return array_values($this->rows);
			}

			foreach ($this->rows as $row) {
				$k = $row->$key;

				if (!empty($k)) {
					$rows[$k] = $row;
				}
			}

			return $rows;
		}

		if ($key !== null) {
			foreach ($this->rows as $row) {
				$k = $row->$key;

				if (!empty($k)) {
					$rows[$k] = $row->$name;
				}
			}

			return $rows;
		}

		foreach ($this->rows as $row) {
			$value = $row->$name;

			if (!empty($value)) {
				$rows[] = $value;
			}
		}

		if ($this->entity->isRelated($name)) {
			$entity = $this->manager->$name;
			$collection = $entity->createCollection();

			if ($this->entity->getRelation($entity) === Entity::RELATION_HAS_ONE) {
				$collection->add($rows);
			} else {
				foreach ($rows as $rows) {
					$collection->add($rows);
				}
			}

			return $collection;
		}

		return $rows;
	}


	/**
	 * Add new values to the collection
	 * 
	 * @param array/HasEntityInterface $rows The new rows
	 */
	public function add ($rows) {
		if (is_array($rows) || (($rows instanceof HasEntityInterface) && $rows->isCollection())) {
			foreach ($rows as $row) {
				$this->offsetSet(null, $row);
			}
		} else if (isset($rows)) {
			$this->offsetSet(null, $rows);
		}

		return $this;
	}


	/**
	 * Filter the rows by a value
	 * 
	 * @param string $name  The value name
	 * @param mixed  $value The value to filter
	 * @param boolean $first Set true to return only the first row found
	 * 
	 * @return SimpleCrud\HasEntityInterface The rows found or null if no value is found and $first parameter is true
	 */
	public function filter ($name, $value, $first = false) {
		$rows = [];

		foreach ($this->rows as $row) {
			if (($row->$name === $value) || (is_array($value) && in_array($row->$name, $value, true))) {
				if ($first === true) {
					return $row;
				}

				$rows[] = $row;
			}
		}

		return $first ? null : $this->entity->createCollection($rows);
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
				$result = $this->manager->$options->selectBy($this);
			} else {
				$result = $this->manager->$name->selectBy($this,
					isset($options['join']) ? $options['join'] : null,
					isset($options['where']) ? $options['where'] : '',
					isset($options['marks']) ? $options['marks'] : null,
					isset($options['orderBy']) ? $options['orderBy'] : null,
					isset($options['limit']) ? $options['limit'] : null
				);
			}

			$this->distribute($result);
		}

		return $this;
	}


	/**
	 * Distribute a row or rowcollection througth all rows
	 * 
	 * @param SimpleCrud\HasEntityInterface $data The row or rowcollection to distribute
	 * @param boolean $bidirectional Set true to distribute also in reverse direccion
	 * 
	 * @return $this
	 */
	public function distribute ($data, $bidirectional = true) {
		if ($data instanceof Row) {
			$data = $data->entity->createCollection([$data]);
		}

		if ($data instanceof RowCollection) {
			$name = $data->entity->name;

			switch ($this->entity->getRelation($data->entity)) {
				case Entity::RELATION_HAS_MANY:
					$foreignKey = $this->entity->foreignKey;

					foreach ($this->rows as $row) {
						if (!isset($row->$name)) {
							$row->$name = $data->entity->createCollection();
						}
					}

					foreach ($data as $row) {
						$id = $row->$foreignKey;

						if (isset($this->rows[$id])) {
							$this->rows[$id]->$name->add($row);
						}
					}

					if ($bidirectional === true) {
						$data->distribute($this, false);
					}

					return $this;

				case Entity::RELATION_HAS_ONE:
					$foreignKey = $data->entity->foreignKey;

					foreach ($this->rows as $row) {
						$row->$name = (($id = $row->$foreignKey) && isset($data[$id])) ? $data[$id] : null;
					}

					if ($bidirectional === true) {
						$data->distribute($this, false);
					}

					return $this;
			}

			throw new \Exception("Cannot set '$name' and '{$this->entity->name}' because is not related or does not exists");
		}
	}
}
