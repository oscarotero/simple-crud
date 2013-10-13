<?php
/**
 * SimpleCrud\RowCollection
 * 
 * Stores a row collection of an entity
 */
namespace SimpleCrud;

class RowCollection implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable, HasEntityInterface {
	private $rows = [];
	private $__entity;

	public function __construct (Entity $entity, array $rows = null) {
		$this->__entity = $entity;

		if ($rows !== null) {
			$this->add($rows);
		}
	}

	public function entity () {
		return $this->__entity;
	}

	public function manager () {
		return $this->__entity->getManager();
	}

	public function isCollection () {
		return true;
	}

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

	public function offsetExists ($offset) {
		return isset($this->rows[$offset]);
	}

	public function offsetUnset ($offset) {
		unset($this->rows[$offset]);
	}

	public function offsetGet ($offset) {
		return isset($this->rows[$offset]) ? $this->rows[$offset] : null;
	}

	public function rewind () {
		return reset($this->rows);
	}

	public function current () {
		return current($this->rows);
	}

	public function key () {
		return key($this->rows);
	}

	public function next () {
		return next($this->rows);
	}

	public function valid () {
		return key($this->rows) !== null;
	}

	public function count () {
		return count($this->rows);
	}

	public function __call ($name, $args) {
		foreach ($this->rows as $row) {
			call_user_func_array([$row, $name], $args);
		}

		return $this;
	}

	public function jsonSerialize () {
		return $this->toArray();
	}

	public function toArray (array $parentEntities = array()) {
		$name = $this->entity()->name;

		if ($parentEntities && in_array($name, $parentEntities)) {
			return null;
		}

		$rows = [];

		foreach ($this->rows as $row) {
			$rows[] = $row->toArray($parentEntities);
		}

		return $rows;
	}


	public function get ($name = null, $key = null) {
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

		return $rows;
	}


	public function set (array $data) {
		foreach ($this->rows as $row) {
			$row->set($data);
		}

		return $this;
	}


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


	public function filter ($name, $value, $first = false) {
		$rows = [];

		foreach ($this->rows as $row) {
			if ($row->$name === $value) {
				if ($first === true) {
					return $row;
				}

				$rows[] = $row;
			}
		}

		return $first ? null : $this->entity()->createCollection($rows);
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

			$this->distribute($manager->$name->selectBy($this, $joins));
		}

		return $this;
	}


	public function distribute ($data, $bidirectional = true) {
		if ($data instanceof Row) {
			$data = $data->entity()->createCollection([$data]);
		}

		if ($data instanceof RowCollection) {
			$thisEntity = $this->entity();
			$dataEntity = $data->entity();

			$name = $dataEntity->name;
			$relation = $thisEntity->getRelation($dataEntity);

			if ($relation === Entity::RELATION_HAS_MANY) {
				$foreignKey = $thisEntity->foreignKey;

				foreach ($this->rows as $row) {
					if (!isset($row->$name)) {
						$row->$name = $dataEntity->createCollection();
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
			}

			if ($relation === Entity::RELATION_HAS_ONE) {
				$foreignKey = $dataEntity->foreignKey;

				foreach ($this->rows as $row) {
					if (($id = $row->$foreignKey) && isset($data[$id])) {
						$row->$name = $data[$id];
					}
				}

				if ($bidirectional === true) {
					$data->distribute($this, false);
				}

				return $this;
			}

			throw new \Exception("Cannot set '$name' and '".$thisEntity->name."' because is not related or does not exists");
		}
	}
}
