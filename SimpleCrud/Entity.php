<?php
/**
 * SimpleCrud\Item
 * 
 * Provides a simple model with basic database operations.
 */
namespace SimpleCrud;

class Entity {
	protected $manager;

	protected $rowClass = 'SimpleCrud\\Row';
	protected $table;
	protected $fields = [];
	protected $foreign_key;


	public function __construct (Manager $manager) {
		$this->manager = $manager;

		if (!$this->table) {
			throw new \Exception("No table defined");
		}

		if (!$this->fields) {
			throw new \Exception("No fields defined");
		}

		if (!$this->foreign_key) {
			$this->foreign_key = "{$this->table}_id";
		}

		$fields = [];

		foreach ($this->fields as $field) {
			$fields[$field] = [$field, "`{$this->table}`.`$field`", "`$field`"];
		}

		$this->fields = $fields;
	}


	public function getFields () {
		return array_keys($this->fields);
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


	public function select ($where = '', $marks = null, $orderBy = null, $limit = null) {
		if ($limit === 0) {
			return new ItemCollection;
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
		if (($statement = $this->manager->execute($query, $marks))) {
			$statement->setFetchMode(\PDO::FETCH_ASSOC);

			if ($fetchOne === true) {
				return $this->create($statement->fetch());
			}

			$result = [];

			while (($row = $statement->fetch())) {
				$result[] = $this->create($row);
			}

			return new RowCollection($result);
		}

		return false;
	}


	public function prepareData (array $data, $new) {
		return $data;
	}


	public function insert (array $data) {
		if (array_diff_key($data, $this->fields)) {
			throw new \Exception("Invalid fields");
		}

		if (!($data = $this->prepareData($data, true))) {
			throw new \Exception("Data not valid");
		}

		$fields = implode(', ', $this->getSqlFields(false,  array_keys($data)));
		$marks = implode(', ', array_fill(0, count($data), '?'));

		try {
			$initialTransation = $this->manager->beginTransaction();

			$this->manager->execute("INSERT INTO `{$this->table}` ($fields) VALUES ($marks)", array_values($data));

			if ($initialTransation) {
				$entity->manager->commit();
			}
		} catch (\Exception $exception) {
			if ($initialTransation) {
				$entity->manager->rollBack();
			}

			throw $exception;
		}

		return $this->manager->lastInsertId();
	}


	public function update (array $data, $where = '', $marks = null, $limit = null) {
		if (array_diff_key($data, $this->fields)) {
			throw new \Exception("Invalid fields");
		}

		if (!($data = $this->prepareData($data, true))) {
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
				$entity->manager->commit();
			}
		} catch (\Exception $exception) {
			if ($initialTransation) {
				$entity->manager->rollBack();
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
				$entity->manager->commit();
			}
		} catch (\Exception $exception) {
			if ($initialTransation) {
				$entity->manager->rollBack();
			}

			throw $exception;
		}
	}
}
