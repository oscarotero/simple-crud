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
	}


	public function jsonSerialize () {
		return $this->get();
	}


	public function getEntity () {
		return $this->__entity;
	}

	public function isCollection () {
		return false;
	}


	public function set (array $data) {
		$fields = $this->getEntity()->getFields();

		foreach ($data as $name => $value) {
			if (!in_array($name, $fields)) {
				throw new \Exception("The property '$name' is not defined");
			}

			$this->$name = $value;
		}
	}


	public function get ($name = null) {
		$fields = $this->getEntity()->getFields();

		if ($name !== null) {
			if (in_array($name, $fields)) {
				return $this->$name;
			}

			throw new \Exception("The property '$name' is not defined");
		}

		$data = [];

		foreach ($fields as $name) {
			$data[$name] = $this->$name;
		}

		return $data;
	}


	public function save () {
		$data = $this->get();

		if (($id = intval($this->id))) {
			$this->getEntity()->update($data, 'id = :id', [':id' => $id], 1);
		} else {
			$this->id = $this->getEntity()->insert($data);
		}
	}


	public function delete () {
		if (empty($this->id)) {
			return false;
		}

		$this->getEntity()->delete('id = :id', [':id' => $id], 1);
	}
}
