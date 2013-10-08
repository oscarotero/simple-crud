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

		foreach ($entity->getDefaults() as $name => $value) {
			$this->$name = $value;
		}
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

		if ($this->getEntity()->isRelated($name)) {
			return $this->$name = $this->load($name);
		}

		return null;
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

		return $this;
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

		if (empty($this->id)) {
			$this->id = $this->getEntity()->insert($data);
		} else {
			$this->getEntity()->update($data, 'id = :id', [':id' => $this->id], 1);
		}

		return $this;
	}


	public function delete () {
		if (empty($this->id)) {
			return false;
		}

		$this->getEntity()->delete('id = :id', [':id' => $this->id], 1);

		return $this;
	}


	public function load ($entities) {
		$entity = $this->getEntity();
		$manager = $entity->getManager();

		foreach ((array)$entities as $name) {
			if (!$entity->isRelated($name)) {
				throw new \Exception("Cannot load '$name' because is not related or does not exists");
			}

			$this->$name = $manager->$name->selectBy($this);
		}

		return $this;
	}
}
