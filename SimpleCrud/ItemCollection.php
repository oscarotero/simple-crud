<?php
/**
 * SimpleCrud\ItemCollection
 * 
 * Simple class to store arrays of results
 */
namespace SimpleCrud;

class ItemCollection implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable {
	private $items = array();

	public function __construct (array $items = null) {
		if ($items !== null) {
			foreach ($items as $item) {
				$this[] = $item;
			}
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

	public function getKeys ($id = 'id', $name = null) {
		$values = array();

		if ($name !== null) {
			foreach ($this->items as $item) {
				$key = $item->$id;

				if (!empty($key)) {
					$values[$key] = $item->$name;
				}
			}

			return $values;
		}

		foreach ($this->items as $item) {
			$key = $item->$id;

			if (!empty($key)) {
				$values[$key] = null;
			}
		}

		return array_keys($values);
	}

	public function isEmpty () {
		return empty($this->items);
	}

	public function setToAll ($name, $value) {
		foreach ($this->items as &$item) {
			$item->$name = $value;
		}
	}

	public function join ($name, $items) {
		$items = ($items instanceof ItemCollection) ? $items : new ItemCollection([$items]);

		if ($this->isEmpty() || $items->isEmpty()) {
			return true;
		}

		return self::joinItems($name, $this, $items);
	}


	private static function joinItems ($name, ItemCollection $Items1, ItemCollection $Items2) {
		$Item1 = $Items1->rewind();
		$Item2 = $Items2->rewind();

		if (!empty($Item1::$relation_field) && ($field = $Item1::$relation_field) && in_array($field, $Item2::getFields())) {
			foreach ($Items1 as $Item) {
				if (!isset($Item->$name)) {
					$Item->$name = new ItemCollection();
				}
			}

			foreach ($Items2 as $Item) {
				$id = $Item->$field;

				if (isset($Items1[$id])) {
					$Items1[$id]->$name->offsetSet(null, $Item);
				}
			}

			return true;
		}

		if (!empty($Item2::$relation_field) && ($field = $Item2::$relation_field) && property_exists($Item1, $field)) {
			foreach ($Items1 as $Item) {
				$id = $Item->$field;

				if (isset($Items2[$id])) {
					$Item->$name = $Items2[$id];
				}
			}

			return true;
		}

		return false;
	}
}
?>