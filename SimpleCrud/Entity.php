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

	protected $manager;

	public $rowClass = 'SimpleCrud\\Row';
	public $rowCollectionClass = 'SimpleCrud\\RowCollection';

	public $name;
	public $table;
	public $fields;
	public $foreignKey;


	public function __construct (Manager $manager, $name) {
		$this->manager = $manager;
		$this->name = $name;
	}


	public function setConfig ($table, array $fields, $foreignKey) {
		$this->table = $table;
		$this->foreignKey = $foreignKey;

		$this->fields = [];

		foreach ($fields as $field) {
			$this->fields[$field] = [$field, "`$table`.`$field`", "`$field`", null];
		}
	}


	public function onInit () {}


	public function getManager () {
		return $this->manager;
	}

	public function getFields () {
		return array_keys($this->fields);
	}


	public function getDefaults () {
		return array_column($this->fields, 3, 0);
	}


	public function getSqlFields ($with_table = false, array $filter = null) {
		$fields = ($filter === null) ? $this->fields : array_intersect_key($this->fields, array_flip($filter));

		return array_column($fields, ($with_table === true) ? 1 : 2, 0);
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


	public function select ($where = '', $marks = null, $orderBy = null, $limit = null) {
		if ($limit === 0) {
			return $this->createCollection();
		}

		$fields = implode(', ', $this->getSqlFields(true));
		$query = "SELECT $fields FROM `{$this->table}`";

		if ($where) {
			$query .= " WHERE $where";
		}

		if ($orderBy) {
			$query .= " ORDER BY $orderBy";
		}

		if ($limit === true) {
			$query .= ' LIMIT 1';
		} elseif ($limit) {
			$query .= ' LIMIT '.(is_array($limit) ? implode(', ', $limit) : $limit);
		}

		return $this->fetch($query, $marks, $limit);
	}


	public function selectBy ($id) {
		if ($id instanceof HasEntityInterface) {
			$entity = $id->entity();
			$relation = $this->getRelation($entity);

			if ($relation === self::RELATION_HAS_ONE) {
				$ids = $id->get('id');

				if (empty($ids)) {
					return $id->isCollection() ? $this->createCollection() : null;
				}

				if ($id->isCollection()) {
					return $this->select("`{$this->table}`.`{$entity->foreignKey}` IN (:id)", [':id' => $ids], null, count($ids));
				}

				return $this->select("`{$this->table}`.`{$entity->foreignKey}` = :id", [':id' => $ids]);
			}

			if ($relation === self::RELATION_HAS_MANY) {
				$ids = $id->get($this->foreignKey);

				if (empty($ids)) {
					return $id->isCollection() ? $this->createCollection() : null;
				}

				if ($id->isCollection()) {
					return $this->select("`{$this->table}`.`id` IN (:id)", [':id' => $ids], null, count($ids));
				}

				return $this->select("`{$this->table}`.`id` = :id", [':id' => $ids], null, true);
			}

			throw new \Exception("The items {$this->table} and {$entity->table} are no related");
		}

		if (empty($id)) {
			return is_array($id) ? [] : false;
		}

		if (is_array($id)) {
			return $this->select('id IN (:id)', [':id' => $id], null, count($id));
		}

		return $this->select('id = :id', [':id' => $id], null, true);
	}


	public function count ($where = '', $marks = null, $limit = null) {
		$query = "SELECT COUNT(*) FROM `{$this->table}`";

		if ($where) {
			$query .= " WHERE $where";
		}

		if ($limit) {
			$query .= ' LIMIT '.(is_array($limit) ? implode(', ', $limit) : $limit);
		}

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
	public function fetch ($query, array $marks = null, $fetchOne = false) {
		if (!($statement = $this->manager->execute($query, $marks))) {
			return false;
		}

		$statement->setFetchMode(\PDO::FETCH_ASSOC);

		if ($fetchOne === true) {
			if (($row = $statement->fetch()) && ($row = $this->prepareDataFromSelection($row))) {
				return $this->create($row);
			}

			return false;
		}

		$result = [];

		while (($row = $statement->fetch())) {
			if (($row = $this->prepareDataFromSelection($row))) {
				$result[] = $this->create($row);
			}
		}

		return $this->createCollection($result);
	}


	public function prepareDataToSave (array $data, $new) {
		return $data;
	}


	public function prepareDataFromSelection (array $data) {
		return $data;
	}


	public function insert (array $data) {
		if (array_diff_key($data, $this->fields)) {
			throw new \Exception("Invalid fields");
		}

		if (!($data = $this->prepareDataToSave($data, true))) {
			throw new \Exception("Data not valid");
		}

		$fields = implode(', ', $this->getSqlFields(false,  array_keys($data)));
		$marks = implode(', ', array_fill(0, count($data), '?'));

		try {
			$initialTransation = $this->manager->beginTransaction();

			$this->manager->execute("INSERT INTO `{$this->table}` ($fields) VALUES ($marks)", array_values($data));

			if ($initialTransation) {
				$this->manager->commit();
			}
		} catch (\Exception $exception) {
			if ($initialTransation) {
				$this->manager->rollBack();
			}

			throw $exception;
		}

		return $this->manager->lastInsertId();
	}


	public function update (array $data, $where = '', $marks = null, $limit = null) {
		if (array_diff_key($data, $this->fields)) {
			throw new \Exception("Invalid fields");
		}

		if (!($data = $this->prepareDataToSave($data, true))) {
			throw new \Exception("Data not valid");
		}

		$data = $this->manager->quote($data);
		$set = [];

		foreach ($this->getSqlFields(false,  array_keys($data)) as $field => $value) {
			$set[] = "`$field` = ".$data[$field];
		}

		$set = implode(', ', $set);
		$query = "UPDATE `{$this->table}` SET $set";

		if ($where) {
			$query .= " WHERE $where";
		}

		if ($limit) {
			$query .= ' LIMIT '.(is_array($limit) ? implode(', ', $limit) : $limit);
		}

		try {
			$initialTransation = $this->manager->beginTransaction();

			$this->manager->execute($query, $marks);

			if ($initialTransation) {
				$this->manager->commit();
			}
		} catch (\Exception $exception) {
			if ($initialTransation) {
				$this->manager->rollBack();
			}

			throw $exception;
		}
	}


	public function delete ($where = '', $marks = null, $limit = null) {
		$query = "DELETE FROM `{$this->table}`";

		if ($where) {
			$query .= " WHERE $where";
		}

		if ($limit) {
			$query .= ' LIMIT '.(is_array($limit) ? implode(', ', $limit) : $limit);
		}

		try {
			$initialTransation = $this->manager->beginTransaction();

			$this->manager->execute($query, $marks);

			if ($initialTransation) {
				$this->manager->commit();
			}
		} catch (\Exception $exception) {
			if ($initialTransation) {
				$this->manager->rollBack();
			}

			throw $exception;
		}
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
