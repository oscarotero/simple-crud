<?php
/**
 * SimpleCrud\Entity
 *
 * Manages a database entity
 */

namespace SimpleCrud;

class Entity {
	const RELATION_HAS_ONE = 1;
	const RELATION_HAS_MANY = 2;

	const FIELDS = 0;
	const FIELDS_SQL = 1;
	const FIELDS_SQL_SELECT = 2;
	const FIELDS_SQL_JOIN = 3;

	protected $manager;

	public $rowClass = 'SimpleCrud\\Row';
	public $rowCollectionClass = 'SimpleCrud\\RowCollection';

	public $name;
	public $table;
	public $fields;
	public $defaults;
	public $foreignKey;


	static private function generateQuery ($query, $where = '', $orderBy = null, $limit = null) {
		if ($where) {
			if (is_array($where)) {
				$where = implode(') AND (', $where);
			}

			$query .= " WHERE ($where)";
		}

		if ($orderBy) {
			$query .= ' ORDER BY '.(is_array($orderBy) ? implode(', ', $orderBy) : $orderBy);
		}

		if ($limit === true) {
			$query .= ' LIMIT 1';
		} else if ($limit) {
			$query .= ' LIMIT '.(is_array($limit) ? implode(', ', $limit) : $limit);
		}

		return $query;
	}


	public function __construct (Manager $manager, $name) {
		$this->manager = $manager;
		$this->name = $name;
	}


	public function setConfig ($table, array $fields, $foreignKey) {
		$this->table = $table;
		$this->foreignKey = $foreignKey;

		$this->fields = $this->defaults = [];

		foreach ($fields as $field) {
			$this->defaults[$field] = null;
			$this->fields[$field] = [$field, "`$field`", "`$table`.`$field`", "`$table`.`$field` as `$this->name.$field`"];
		}
	}


	public function onInit () {}


	public function getManager () {
		return $this->manager;
	}

	public function getFields ($mode = 0, array $filter = null) {
		if ($filter === null) {
			return array_column($this->fields, $mode, 0);
		}

		return array_column(array_intersect_key($this->fields, array_flip($filter)), $mode, 0);
	}


	protected function createFromSelection (array $row, $expand) {
		if ($expand === false) {
			return ($row = $this->dataFromDatabase($row)) ? $this->create($row) : false;
		}

		$fields = $joinFields = [];

		foreach ($row as $name => $value) {
			if (strpos($name, '.') === false) {
				$fields[$name] = $value;
				continue;
			}

			list($name, $fieldName) = explode('.', $name);

			if (!isset($joinFields[$name])) {
				$joinFields[$name] = [];
			}

			$joinFields[$name][$fieldName] = $value;
		}

		if (!($row = $this->dataFromDatabase($fields))) {
			return false;
		}

		$row = $this->create($row);

		foreach ($joinFields as $name => $values) {
			$row->$name = $this->manager->$name->create($values);
		}

		return $row;
	}


	public function create (array $data = null) {
		$row = new $this->rowClass($this);

		if ($data !== null) {
			$row->set($data);
		}

		return $row;
	}


	public function createCollection (array $rows = null) {
		return new $this->rowCollectionClass($this, $rows);
	}


	public function select ($where = '', $marks = null, $orderBy = null, $limit = null, array $joins = null) {
		if ($limit === 0) {
			return $this->createCollection();
		}

		$fields = implode(', ', $this->getFields(self::FIELDS_SQL_SELECT));
		$query = '';
		$load = [];

		if ($joins !== null) {
			foreach ($joins as $k => $join) {
				if (is_array($join)) {
					$load[$k] = $join;
					continue;
				}

				$entity = $this->manager->$join;
				$relation = $this->getRelation($entity);

				if ($relation === self::RELATION_HAS_ONE) {
					$fields .= ', '.implode(', ', $entity->getFields(self::FIELDS_SQL_JOIN));
					$query .= " LEFT JOIN `{$entity->table}` ON (`{$entity->table}`.`id` = `{$this->table}`.`{$entity->foreignKey}`)";

					continue;
				}

				if ($relation === self::RELATION_HAS_MANY) {
					$load[$k] = $join;

					continue;
				}

				throw new \Exception("The items '{$this->table}' and '{$entity->table}' are no related");
			}
		}

		$query = self::generateQuery("SELECT $fields FROM `{$this->table}`$query", $where, $orderBy, $limit);

		$result = $this->fetch($query, $marks, $limit, (bool)$joins);

		if ($load) {
			$result->load($load);
		}

		return $result;
	}


	public function selectBy ($id, array $joins = null) {
		if ($id instanceof HasEntityInterface) {
			$entity = $id->entity();
			$relation = $this->getRelation($entity);

			if ($relation === self::RELATION_HAS_ONE) {
				$ids = $id->get('id');

				if (empty($ids)) {
					return $id->isCollection() ? $this->createCollection() : null;
				}

				if ($id->isCollection()) {
					return $this->select("`{$this->table}`.`{$entity->foreignKey}` IN (:id)", [':id' => $ids], null, count($ids), $joins);
				}

				return $this->select("`{$this->table}`.`{$entity->foreignKey}` = :id", [':id' => $ids], null, null, $joins);
			}

			if ($relation === self::RELATION_HAS_MANY) {
				$ids = $id->get($this->foreignKey);

				if (empty($ids)) {
					return $id->isCollection() ? $this->createCollection() : null;
				}

				if ($id->isCollection()) {
					return $this->select("`{$this->table}`.`id` IN (:id)", [':id' => $ids], null, count($ids), $joins);
				}

				return $this->select("`{$this->table}`.`id` = :id", [':id' => $ids], null, true, $joins);
			}

			throw new \Exception("The items {$this->table} and {$entity->table} are no related");
		}

		if (empty($id)) {
			return is_array($id) ? $this->createCollection() : false;
		}

		if (is_array($id)) {
			return $this->select('id IN (:id)', [':id' => $id], null, count($id), $joins);
		}

		return $this->select('id = :id', [':id' => $id], null, true, $joins);
	}


	public function count ($where = '', $marks = null, $limit = null) {
		$query = self::generateQuery("SELECT COUNT(*) FROM `{$this->table}`", $where, null, $limit);

		if (($statement = $this->manager->execute($query, $marks)) && ($result = $statement->fetch(\PDO::FETCH_NUM))) {
			return (int)$result[0];
		}

		return false;
	}


	/**
	 * Execute a query and returns the statement object with the result
	 * 
	 * @param  string $query The Mysql query to execute
	 * @param  array $marks The marks passed to the statement
	 *
	 * @return PDOStatement The result
	 */
	public function fetch ($query, array $marks = null, $fetchOne = false, $expand = false) {
		if (!($statement = $this->manager->execute($query, $marks))) {
			return false;
		}

		$statement->setFetchMode(\PDO::FETCH_ASSOC);

		if ($fetchOne === true) {
			return ($row = $statement->fetch()) ? $this->createFromSelection($row, $expand) : false;
		}

		$result = [];

		while (($row = $statement->fetch())) {
			if (($row = $this->createFromSelection($row, $expand))) {
				$result[] = $row;
			}
		}

		return $this->createCollection($result);
	}


	public function dataToDatabase (array $data, $new) {
		return $data;
	}


	public function dataFromDatabase (array $data) {
		return $data;
	}


	public function insert (array $data) {
		if (array_diff_key($data, $this->fields)) {
			throw new \Exception("Invalid fields");
		}

		if (!($data = $this->dataToDatabase($data, true))) {
			throw new \Exception("Data not valid");
		}

		$fields = implode(', ', $this->getFields(self::FIELDS_SQL, array_keys($data)));
		$quoted = implode(', ', $this->manager->quote($data));

		$query = "INSERT INTO `{$this->table}` ($fields) VALUES ($quoted)";

		$this->manager->executeTransaction(function () use ($query) {
			$this->manager->execute($query);
		});

		$data['id'] = $this->manager->lastInsertId();

		return $this->dataFromDatabase($data);
	}


	public function update (array $data, $where = '', $marks = null, $limit = null) {
		if (array_diff_key($data, $this->fields)) {
			throw new \Exception("Invalid fields");
		}

		if (!($data = $this->dataToDatabase($data, true))) {
			throw new \Exception("Data not valid");
		}

		$quoted = $this->manager->quote($data);
		unset($quoted['id']);
		
		$set = [];

		foreach ($this->getFields(self::FIELDS_SQL, array_keys($quoted)) as $name => $field) {
			$set[] = "$field = ".$quoted[$name];
		}

		$set = implode(', ', $set);
		$query = self::generateQuery("UPDATE `{$this->table}` SET $set", $where, null, $limit);

		$this->manager->executeTransaction(function () use ($query, $marks) {
			$this->manager->execute($query, $marks);
		});

		return $this->dataFromDatabase($data);
	}


	public function delete ($where = '', $marks = null, $limit = null) {
		$query = self::generateQuery("DELETE FROM `{$this->table}`", $where, null, $limit);

		$this->manager->executeTransaction(function () use ($query, $marks) {
			$this->manager->execute($query, $marks);
		});
	}


	public function isRelated ($entity) {
		if (!($entity instanceof Entity) && !($entity = $this->manager->$entity)) {
			return false;
		}

		return ($this->getRelation($entity) !== null);
	}


	public function getRelation (Entity $entity) {
		if (isset($entity->fields[$this->foreignKey])) {
			return self::RELATION_HAS_MANY;
		}

		if (isset($this->fields[$entity->foreignKey])) {
			return self::RELATION_HAS_ONE;
		}
	}
}
