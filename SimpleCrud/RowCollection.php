<?php
/**
 * SimpleCrud\RowCollection
 * 
 * Simple class to store arrays of results
 */
namespace SimpleCrud;

class RowCollection implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable {
	private $rows = [];

	public function __construct ($rows = null) {
		if ($rows !== null) {
			$this->add($rows);
		}
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

	public function jsonSerialize () {
		return $this->rows;
	}

	public function get ($name = null, $key = null) {
		$rows = [];

		if ($name === null) {
			if ($key === null) {
				foreach ($this->rows as $row) {
					$rows[] = $row->get();
				}

				return $rows;
			}

			foreach ($this->rows as $row) {
				$k = $row->$key;

				if (!empty($k)) {
					$rows[$k] = $row->get();
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
	}

	public function add ($rows) {
		if (is_array($rows)) {
			foreach ($rows as $row) {
				$this->offsetSet(null, $row);
			}
		} else if (isset($rows)) {
			$this->offsetSet(null, $rows);
		}
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

		return $first ? null : new ItemCollection($rows);
	}
}
