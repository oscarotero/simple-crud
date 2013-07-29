<?php
/**
 * SimpleCrud\ItemCollection
 * 
 * Simple class to store arrays of results
 */
namespace SimpleCrud;

class ItemCollection implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable {
	private $items = array();

	public function __construct ($items = null) {
		if ($items !== null) {
			$this->add($items);
		}
	}

	public function offsetSet ($offset, $value) {
		if (is_null($offset)) {
			$offset = $value->id;
		}

		if (is_null($offset)) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	public function offsetExists ($offset) {
		return isset($this->items[$offset]);
	}

	public function offsetUnset ($offset) {
		unset($this->items[$offset]);
	}

	public function offsetGet ($offset) {
		return isset($this->items[$offset]) ? $this->items[$offset] : null;
	}

	public function rewind () {
		return reset($this->items);
	}

	public function current () {
		return current($this->items);
	}

	public function key () {
		return key($this->items);
	}

	public function next () {
		return next($this->items);
	}

	public function valid () {
		return key($this->items) !== null;
	}

	public function count () {
		return count($this->items);
	}

	public function jsonSerialize () {
		return $this->items;
	}

	public function get ($name = null, $key = null) {
		$values = [];

		if ($name === null) {
			foreach ($this->items as $item) {
				$values[] = $item->get();
			}
		}

		if ($key !== null) {
			foreach ($this->items as $item) {
				$k = $item->$key;

				if (!empty($k)) {
					$values[$k] = $item->$name;
				}
			}

			return $values;
		}

		foreach ($this->items as $item) {
			$value = $item->$name;

			if (!empty($value)) {
				$values[] = $value;
			}
		}

		return $values;
	}

	public function load ($joinItems, array $subJoinItems = null) {
		if (!($item = $this->rewind())) {
			return null;
		}

		if (!is_array($joinItems)) {
			$joinItems = array($joinItems => $subJoinItems);
		}

		foreach ($joinItems as $joinItems => $subJoinItems) {
			if (is_int($joinItems)) {
				$joinItems = $subJoinItems;
				$subJoinItems = null;
			}

			$foreignClass = $item::ITEMS_NAMESPACE.ucfirst($joinItems);

			$this->set($foreignClass::selectBy($this, $subJoinItems));
		}
	}

	public function set ($name, $value = null) {
		if ($name instanceof Item) {
			$name = new ItemCollection($name);
		}

		if ($name instanceof ItemCollection) {
			if (!($item = $this->rewind()) || !($joinItem = $name->rewind())) {
				return null;
			}
			
			if (!($relation = $item::getRelation($joinItem))) {
				throw new \Exception('The items '.$item::TABLE.' and '.$joinItem::TABLE.' cannot be related');
			}
			
			$prefix = lcfirst(substr(get_class($joinItem), strlen($item::ITEMS_NAMESPACE)));
			list($type, $foreign_key) = $relation;

			switch ($type) {
				case Item::RELATION_HAS_MANY:
					foreach ($name as $item) {
						$id = $item->$foreign_key;

						if (isset($this->items[$id]->$prefix)) {
							$this->items[$id]->$prefix = new ItemCollection([$item]);
						} else {
							$v = $this->items[$id]->$prefix;
							$v[] = $item;
						}
					}
					return null;

				case Item::RELATION_HAS_ONE:
					foreach ($this->items as $item) {
						$id = $item->$foreign_key;
						if (isset($name[$id])) {
							$item->$prefix = $name[$id];
						}
					}
					return null;
			}
		}

		foreach ($this->items as &$item) {
			$item->$name = $value;
		}
	}

	public function add ($items) {
		if (is_array($items) || ($items instanceof ItemCollection)) {
			foreach ($items as $item) {
				$this[] = $item;
			}
		} else if (isset($items)) {
			$this[] = $items;
		}
	}

	public function filter ($name, $value, $first = false) {
		$result = [];

		foreach ($this->items as $item) {
			if ($item->$name === $value) {
				if ($first === true) {
					return $item;
				}

				$result[] = $item;
			}
		}

		return $first ? null : new ItemCollection($result);
	}
}
