<?php
/**
 * SimpleCrud\Entity
 *
 * Manages a database entity
 */

namespace SimpleCrud;

use PDO;

class Entity {
	const RELATION_HAS_ONE = 1;
	const RELATION_HAS_MANY = 2;

	const FIELDS = 0;
	const FIELDS_SQL = 1;
	const FIELDS_SQL_SELECT = 2;
	const FIELDS_SQL_JOIN = 3;
	const FIELDS_DATA_TYPE = 4;

	public $manager;

	public $rowClass;
	public $rowCollectionClass;

	public $name;
	public $table;
	public $fields;
	public $defaults;
	public $foreignKey;


	/**
	 * Private function to generate the mysql queries
	 */
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


	/**
	 * Set the configuration for this entity (used by Manager)
	 * 
	 * @param string $table The database table name
	 * @param array  $fields The fields in the table name
	 * @param string $foreignKey The foreign key name used to relate with other entities
	 * @param string $rowClass The class name used for rows
	 * @param string $rowCollectionClass The class name used for row collections
	 */
	public function setConfig ($table, array $fields, $foreignKey, $rowClass, $rowCollectionClass) {
		$this->table = $table;
		$this->foreignKey = $foreignKey;
		$this->rowClass = $rowClass;
		$this->rowCollectionClass = $rowCollectionClass;

		$this->fields = $this->defaults = [];

		foreach ($fields as $field => $type) {
			$this->defaults[$field] = null;
			$this->fields[$field] = [$field, "`$field`", "`$table`.`$field`", "`$table`.`$field` as `$this->name.$field`", $type];
		}
	}


	/**
	 * Callback onInit
	 */
	public function onInit () {}


	/**
	 * Returns the fields of the entity
	 * 
	 * @param  integer $mode One of the FIELD_* contants values
	 * @param  array $filter Filter the fields
	 * 
	 * @return array
	 */
	public function getFields ($mode = 0, array $filter = null) {
		if ($filter === null) {
			return array_column($this->fields, $mode, 0);
		}

		return array_column(array_intersect_key($this->fields, array_flip($filter)), $mode, 0);
	}


	/**
	 * Create a row instance from the result of a select query
	 * 
	 * @param array $row The select values
	 * @param boolean $expand True to expand the results
	 * 
	 * @return SimpleCrud\Row
	 */
	protected function createFromSelection (array $row, $expand) {
		if ($expand === false) {
			return ($row = $this->dataFromDatabase($row)) ? $this->create($row)->emptyChanges() : false;
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

		$row = $this->create($row)->emptyChanges();

		foreach ($joinFields as $name => $values) {
			$row->$name = empty($values['id']) ? null : $this->manager->$name->create($values)->emptyChanges();
		}

		return $row;
	}


	/**
	 * Creates a new row instance
	 * 
	 * @param array $data The values of the row
	 * @param boolean $onlyDeclaredFields Set true to only set declared fields
	 * 
	 * @return SimpleCrud\Row
	 */
	public function create (array $data = null, $onlyDeclaredFields = false) {
		$row = new $this->rowClass($this);

		if ($data !== null) {
			$row->set($data, $onlyDeclaredFields);
		}

		return $row;
	}


	/**
	 * Creates a new rowCollection instance
	 * 
	 * @param array $rows Rows added to this collection
	 * 
	 * @return SimpleCrud\RowCollection
	 */
	public function createCollection (array $rows = null) {
		$collection = new $this->rowCollectionClass($this);

		if ($rows !== null) {
			$collection->add($rows);
		}

		return $collection;
	}


	/**
	 * Executes a SELECT in the database
	 * 
	 * @param string/array $where
	 * @param array $marks
	 * @param string/array $orderBy
	 * @param int/array $limit
	 * @param array $joins Optional entities to join
	 * @param array $from Extra tables used in the query
	 * 
	 * @return mixed The row or rowcollection with the result or null
	 */
	public function select ($where = '', $marks = null, $orderBy = null, $limit = null, array $joins = null, array $from = null) {
		if ($limit === 0) {
			return $this->createCollection();
		}

		$fields = implode(', ', $this->getFields(self::FIELDS_SQL_SELECT));
		$query = '';
		$load = [];

		if ($joins !== null) {
			foreach ($joins as $name => $options) {
				if (!is_array($options)) {
					$name = $options;
					$options = [];
				}

				if (!empty($options['join'])) {
					$load[$name] = $options;
					continue;
				}

				$entity = $this->manager->$name;
				$relation = $this->getRelation($entity);

				if ($relation === self::RELATION_HAS_ONE) {
					$fields .= ', '.implode(', ', $entity->getFields(self::FIELDS_SQL_JOIN));
					$on = "`{$entity->table}`.`id` = `{$this->table}`.`{$entity->foreignKey}`";

					if (!empty($options['where'])) {
						$on .= ' AND ('.$options['where'].')';

						if (!empty($options['marks'])) {
							$marks = array_replace($marks, $options['marks']);
						}
					}

					$query .= " LEFT JOIN `{$entity->table}` ON ($on)";

					continue;
				}

				if ($relation === self::RELATION_HAS_MANY) {
					$load[$name] = $options;

					continue;
				}

				throw new \Exception("The items '{$this->table}' and '{$entity->table}' are no related");
			}
		}

		if ($from) {
			$from[] = "`{$this->table}`";
			$from = implode(', ', $from);
		} else {
			$from = "`{$this->table}`";
		}

		$query = self::generateQuery("SELECT $fields FROM {$from}{$query}", $where, $orderBy, $limit);
		$result = $this->fetch($query, $marks, (isset($limit[1]) ? $limit[1] : $limit), (bool)$joins);

		if ($load && $result) {
			$result->load($load);
		}

		return $result;
	}


	/**
	 * Executes a selection by id or by relation with other rows or collections
	 * 
	 * @param mixed $id The id/ids, row or rowCollection used to select
	 * @param string/array $where
	 * @param array $marks
	 * @param string/array $orderBy
	 * @param int/array $limit
	 * @param array $joins Optional entities to join
	 * 
	 * @return mixed The row or rowcollection with the result or null
	 */
	public function selectBy ($id, $where = '', $marks = null, $orderBy = null, $limit = null, array $joins = null) {
		if (empty($id)) {
			return is_array($id) ? $this->createCollection() : false;
		}

		$where = empty($where) ? [] : (array)$where;
		$marks = empty($marks) ? [] : (array)$marks;

		if ($id instanceof HasEntityInterface) {
			if (!($relation = $this->getRelation($id->entity))) {
				throw new \Exception("The items {$this->table} and {$id->entity->table} are no related");
			}

			if ($relation === self::RELATION_HAS_ONE) {
				$ids = $id->get('id');
				$foreignKey = $id->entity->foreignKey;
				$fetch = null;
			} else if ($relation === self::RELATION_HAS_MANY) {
				$ids = $id->get($this->foreignKey);
				$foreignKey = 'id';
				$fetch = true;
			}

			if (empty($ids)) {
				return $id->isCollection() ? $this->createCollection() : null;
			}

			$where[] = "`{$this->table}`.`$foreignKey` IN (:id)";
			$marks[':id'] = $ids;

			if ($limit === null) {
				$limit = ($id->isCollection() && $fetch) ? count($ids) : $fetch;
			}
		} else {
			$where[] = 'id IN (:id)';
			$marks[':id'] = $id;
			
			if ($limit === null) {
				$limit = is_array($id) ? count($id) : true;
			}
		}

		return $this->select($where, $marks, $orderBy, $limit, $joins);
	}


	/**
	 * Execute a count query in the database
	 * 
	 * @param  string/array $where
	 * @param  array $marks
	 * @param  int/array $limit
	 * 
	 * @return int 
	 */
	public function count ($where = '', $marks = null, $limit = null) {
		$query = self::generateQuery("SELECT COUNT(*) FROM `{$this->table}`", $where, null, $limit);

		$statement = $this->manager->execute($query, $marks);
		$result = $statement->fetch(\PDO::FETCH_NUM);
		
		return (int)$result[0];
	}


	/**
	 * Execute a query and returns the statement object with the result
	 * 
	 * @param  string $query The Mysql query to execute
	 * @param  array $marks The marks passed to the statement
	 * @param  boolean $fetchOne Set true to returns only the first value
	 * @param  boolean $expand Used to expand values in subrows on JOINs
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


	/**
	 * Default data converter/validator from database
	 * 
	 * @param  array $data The values before insert to database
	 */
	public function dataToDatabase (array $data, $new) {
		return $data;
	}


	/**
	 * Default data converter from database
	 * 
	 * @param  array $data The database format values
	 */
	public function dataFromDatabase (array $data) {
		return $data;
	}


	/**
	 * Executes an 'insert' query in the database
	 * 
	 * @param  array  $data  The values to insert
	 * @param  boolean $duplicateKey Set true if you can avoid duplicate key errors
	 * 
	 * @return array The new values of the inserted row
	 */
	public function insert (array $data, $duplicateKey = false) {
		if (array_diff_key($data, $this->fields)) {
			throw new \Exception("Invalid fields");
		}

		if (!($data = $this->dataToDatabase($data, true))) {
			throw new \Exception("Data not valid");
		}

		if (empty($data['id'])) {
			unset($data['id']);
		}

		$quoted = $this->manager->quote($data, $this->getFields(self::FIELDS_DATA_TYPE));

		$fields = $values = [];

		foreach ($this->getFields(self::FIELDS_SQL, array_keys($quoted)) as $name => $field) {
			$fields[] = $field;
			$values[] = $quoted[$name];
		}

		$queryFields = implode(', ', $fields);
		$queryValues = implode(', ', $values);

		$query = "INSERT INTO `{$this->table}` ($queryFields) VALUES ($queryValues)";

		if ($duplicateKey) {
			$update = ['id = LAST_INSERT_ID(id)'];

			foreach ($fields as $k => $field) {
				$update[] = "$field = ".$values[$k];
			}

			$query .= ' ON DUPLICATE KEY UPDATE '.implode(', ', $update);
		}

		$data['id'] = $this->manager->executeTransaction(function () use ($query) {
			$this->manager->execute($query);

			return $this->manager->lastInsertId();
		});

		return $this->dataFromDatabase($data);
	}


	/**
	 * Executes an 'update' query in the database
	 * 
	 * @param  array  $data  The values to update
	 * @param  string/array $where
	 * @param  array $marks
	 * @param  int/array $limit
	 * 
	 * @return array The new values of the updated row
	 */
	public function update (array $data, $where = '', $marks = null, $limit = null) {
		if (array_diff_key($data, $this->fields)) {
			throw new \Exception("Invalid fields");
		}

		if (!($data = $this->dataToDatabase($data, false))) {
			throw new \Exception("Data not valid");
		}

		$quoted = $this->manager->quote($data, $this->getFields(self::FIELDS_DATA_TYPE));
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


	/**
	 * Execute a delete query in the database
	 * 
	 * @param  string/array $where
	 * @param  array $marks
	 * @param  int/array $limit
	 */
	public function delete ($where = '', $marks = null, $limit = null) {
		$query = self::generateQuery("DELETE FROM `{$this->table}`", $where, null, $limit);

		$this->manager->executeTransaction(function () use ($query, $marks) {
			$this->manager->execute($query, $marks);
		});
	}


	/**
	 * Check if this entity is related with other
	 * 
	 * @param SimpleCrud\Entity / string $entity The entity object or name
	 * 
	 * @return boolean
	 */
	public function isRelated ($entity) {
		if (!($entity instanceof Entity) && !($entity = $this->manager->$entity)) {
			return false;
		}

		return ($this->getRelation($entity) !== null);
	}


	/**
	 * Returns the relation type of this entity with other
	 * 
	 * @param SimpleCrud\Entity $entity
	 * 
	 * @return int One of the RELATION_* constants values or null
	 */
	public function getRelation (Entity $entity) {
		if (isset($entity->fields[$this->foreignKey])) {
			return self::RELATION_HAS_MANY;
		}

		if (isset($this->fields[$entity->foreignKey])) {
			return self::RELATION_HAS_ONE;
		}
	}
}
