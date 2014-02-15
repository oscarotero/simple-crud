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

	public $manager;

	public $name;
	public $table;
	public $fields;
	public $defaults;
	public $foreignKey;

	public $rowClass;
	public $rowCollectionClass;

	private $fieldsInfo = [];




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
	 * init callback
	 */
	public function init () {}



	/**
	 * Returns an array with the fields names
	 *
	 * @return array in the format [name => name]
	 */
	public function getFieldsNames () {
		if (!isset($this->fieldsInfo['names'])) {
			$keys = array_keys($this->fields);
			$this->fieldsInfo['names'] = array_combine($keys, $keys);
		}

		return $this->fieldsInfo['names'];
	}



	/**
	 * Returns an array with the fields defaults values of all fields
	 *
	 * @return array in the format [name => value]
	 */
	public function getDefaults () {
		if (!isset($this->fieldsInfo['defaults'])) {
			$this->fieldsInfo['defaults'] = array_fill_keys($this->getFieldsNames(), null);
		}

		return $this->fieldsInfo['defaults'];
	}



	/**
	 * Returns an array with the fields names ready for select queries
	 *
	 * @param array $filter Fields names to retrieve.
	 *
	 * @return array in the format [name => escapedName]
	 */
	public function getEscapedFieldsForSelect (array $filter = null) {
		if (!isset($this->fieldsInfo['select'])) {
			$this->fieldsInfo['select'] = [];

			foreach ($this->fields as $name => $field) {
				$this->fieldsInfo['select'][$name] = $field->getEscapedNameForSelect();
			}
		}

		if ($filter === null) {
			return $this->fieldsInfo['select'];
		}

		return array_intersect_key($this->fieldsInfo['select'], array_flip($filter));
	}



	/**
	 * Returns an array with the fields names ready for join queries
	 *
	 * @param array $filter Fields names to retrieve.
	 *
	 * @return array in the format [name => escapedName]
	 */
	public function getEscapedFieldsForJoin (array $filter = null) {
		if (!isset($this->fieldsInfo['join'])) {
			$this->fieldsInfo['join'] = [];

			foreach ($this->fields as $name => $field) {
				$this->fieldsInfo['join'][$name] = $field->getEscapedNameForJoin();
			}
		}

		if ($filter === null) {
			return $this->fieldsInfo['join'];
		}

		return array_intersect_key($this->fieldsInfo['join'], array_flip($filter));
	}



	/**
	 * Create a row instance from the result of a select query
	 * 
	 * @param array $row The selected values
	 * @param boolean $expand True to expand the results (used if the select has joins)
	 * 
	 * @return SimpleCrud\Row
	 */
	public function createFromSelection (array $row, $expand = false) {
		foreach ($row as $key => &$value) {
			$value = $this->fields[$key]->dataFromDatabase($value);
		}

		if ($expand === false) {
			return ($row = $this->dataFromDatabase($row)) ? $this->create($row)->emptyChanges() : false;
		}

		$fields = $joinFields = [];

		foreach ($row as $name => $value) {
			if (strpos($name, '.') === false) {
				$fields[$name] = $value;
				continue;
			}

			list($name, $fieldName) = explode('.', $name, 2);

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
			$row->$name = empty($values['id']) ? null : $this->manager->$name->createFromSelection($values);
		}

		return $row;
	}



	/**
	 * Creates a new row instance
	 * 
	 * @param array $data The values of the row
	 * @param boolean $onlyDeclaredFields Set true to discard values in undeclared fields
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

		$fields = implode(', ', $this->getEscapedFieldsForSelect());
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
					$fields .= ', '.implode(', ', $entity->getEscapedFieldsForJoin());
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
	 * Execute a query and return the first row found
	 * 
	 * @param  string $query The Mysql query to execute
	 * @param  array $marks The marks passed to the statement
	 * @param  boolean $expand Used to expand values of rows in JOINs
	 *
	 * @return SimpleCrud\Row or false
	 */
	public function fetchOne ($query, array $marks = null, $expand = false) {
		if (!($statement = $this->manager->execute($query, $marks))) {
			return false;
		}

		$statement->setFetchMode(\PDO::FETCH_ASSOC);

		return ($row = $statement->fetch()) ? $this->createFromSelection($row, $expand) : false;
	}



	/**
	 * Execute a query and return all rows found
	 * 
	 * @param  string $query The Mysql query to execute
	 * @param  array $marks The marks passed to the statement
	 * @param  boolean $expand Used to expand values in subrows on JOINs
	 *
	 * @return PDOStatement The result
	 */
	public function fetchAll ($query, array $marks = null, $expand = false) {
		if (!($statement = $this->manager->execute($query, $marks))) {
			return false;
		}

		$statement->setFetchMode(\PDO::FETCH_ASSOC);

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
	 * Prepare the data before save into database (used by update and insert)
	 *
	 * @param array &$data The data to save
	 * @param bool $new True if it's a new value (insert)
	 */
	private function prepareDataToDatabase (array &$data, $new) {
		if (!is_array($data = $this->dataToDatabase($data, $new))) {
			throw new \Exception("Data not valid");
		}

		if (array_diff_key($data, $this->getFieldsNames())) {
			throw new \Exception("Invalid fields");
		}

		//Transform data before save to database
		$dbData = [];

		foreach ($data as $key => $value) {
			$dbData[$key] = $this->fields[$key]->dataToDatabase($value);
		}

		return $dbData;
	}



	/**
	 * Removes unchanged data before save into database (used by update and insert)
	 *
	 * @param array $data The original data
	 * @param array $prepared The prepared data
	 * @param array $changedFields Array of changed fields.
	 */
	private function filterDataToSave (array $data, array $prepared, array $changedFields) {
		$filtered = [];

		foreach ($data as $name => $value) {
			if (isset($changedFields[$name]) || ($value !== $prepared[$name])) {
				$filtered[$name] = $prepared[$name];
			}
		}

		return $filtered;
	}



	/**
	 * Executes an 'insert' query in the database
	 * 
	 * @param  array  $data  The values to insert
	 * @param  boolean $duplicateKey Set true if you can avoid duplicate key errors
	 * 
	 * @return array The new values of the inserted row
	 */
	public function insert (array $data, $duplicateKey = false, array $changedFields = null) {
		$preparedData = $this->prepareDataToDatabase($data, true);

		if ($changedFields !== null) {
			$preparedData = $this->filterDataToSave($data, $preparedData, $changedFields);
		}

		unset($preparedData['id']);

		if (empty($preparedData)) {
			$query = "INSERT INTO `{$this->table}` (`id`) VALUES (NULL)";
			$marks = null;
		} else {
			$fields = array_keys($preparedData);

			$query = 'INSERT INTO `'.$this->table.'` (`'.implode('`, `', $fields).'`) VALUES (:'.implode(', :', $fields).')';
			$marks = [];

			foreach ($preparedData as $key => $value) {
				$marks[":$key"] = $value;
			}

			if ($duplicateKey) {
				$query .= ' ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)';

				foreach ($fields as $name) {
					$query .= ", {$name} = :{$name}";
				}
			}

		}

		$data['id'] = $this->manager->executeTransaction(function () use ($query, $marks) {
			$this->manager->execute($query, $marks);

			return $this->manager->lastInsertId();
		});

		return $data;
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
	public function update (array $data, $where = '', $marks = null, $limit = null, array $changedFields = null) {
		$preparedData = $this->prepareDataToDatabase($data, true);

		if ($changedFields !== null) {
			$preparedData = $this->filterDataToSave($data, $preparedData, $changedFields);
		}

		unset($preparedData['id']);

		if (empty($preparedData)) {
			return $data;
		}

		$query = [];
		$marks = $marks ?: [];

		foreach ($preparedData as $key => $value) {
			$marks[":__{$key}"] = $value;
			$query[] = "`{$key}` = :__{$key}";
		}

		$query = implode(', ', $query);
		$query = self::generateQuery("UPDATE `{$this->table}` SET {$query}", $where, null, $limit);

		$this->manager->executeTransaction(function () use ($query, $marks) {
			$this->manager->execute($query, $marks);
		});

		return $data;
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
