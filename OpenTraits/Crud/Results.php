<?php
/**
 * OpenTraits\Crud\Results
 * 
 * Simple class to store arrays of results
 */
namespace OpenTraits\Crud;

class Results implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable {
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

	public function getKeys ($id = 'id') {
		$ids = array();

		foreach ($this->items as $item) {
			$value = $item->$id;

			if (!empty($value)) {
				$ids[$value] = null;
			}
		}

		return array_keys($ids);
	}

	public function setToAll ($name, $property) {
		foreach ($this->items as &$item) {
			$item->$name = $property;
		}
	}

	public function join ($name, $items) {
		$result = ($items instanceof Results) ? $items : new Results([$items]);

		return self::joinItems($name, $this, $result);
	}


	private static function joinItems ($name, $Items1, $Items2) {
		if (empty($Items1) || empty($Items2)) {
			return true;
		}

		$Item1 = $Items1->rewind();
		$Item2 = $Items2->rewind();

		if (!empty($Item1::$relation_field)) {
			$field = $Item1::$relation_field;

			if (property_exists($Item2, $field)) {
				foreach ($Items1 as $Item) {
					if (!isset($Item->$name)) {
						$Item->$name = new Results();
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
		}

		if (!empty($Item2::$relation_field)) {
			$field = $Item2::$relation_field;

			if (property_exists($Item1, $field)) {
				foreach ($Items1 as $Item) {
					$id = $Item->$field;

					if (isset($Items2[$id])) {
						$Item->$name = $Items2[$id];
					}
				}

				return true;
			}
		}

		return false;
	}
}
?>