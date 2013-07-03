<?php
/**
 * SimpleCrud\Item
 * 
 * Provides a simple model with basic database operations.
 */
namespace SimpleCrud;

class Item {
	const RELATION_HAS_ONE = 1;
	const RELATION_HAS_MANY = 2;
	const ONUPDATE_PASS_DIFF_VALUES = false;

	protected static $__connection;
	protected static $__inTransaction = false;
	protected static $__debug = false;


	/**
	 * Set the database connection.
	 * 
	 * @param PDO $Db The database object
	 */
	public static function setConnection (\PDO $Connection) {
		static::$__connection = $Connection;
	}


	/**
	 * Enable, disable, get debugs
	 *
	 * @param bool Enable or disable. Null to get the debug result
	 * 
	 * @return array or null
	 */
	public static function debug ($enabled = null) {
		$debug = static::$__debug;

		if ($enabled === true) {
			static::$__debug = is_array(static::$__debug) ? static::$__debug : [];
		} else if ($enabled === false) {
			static::$__debug = false;
		}

		return $debug;
	}


	/**
	 * Returns the list of all fields in SQL format
	 * 
	 * @param array $filter
	 * 
	 * @return string
	 */
	public static function getSqlFields (array $filter = null) {
		$table = static::TABLE;
		$class = get_called_class();
		$prefix = lcfirst(substr($class, strlen(static::ITEMS_NAMESPACE)));
		$fields = getItemVars($class);

		if ($filter !== null) {
			$fields = array_intersect($fields, $filter);
		}

		$result = [];

		foreach ($fields as $field) {
			$result[] = "`$table`.`$field` as `$prefix.$field`";
		}

		return implode(', ', $result);
	}


	/**
	 * Creates a empty object or, optionally, fill with some data
	 * 
	 * @param array $data Data to fill the item.
	 * 
	 * @return object The instantiated objec
	 */
	public static function create (array $data = null) {
		$item = new static();

		if ($data !== null) {
			$item->set($data);
		}

		return $item;
	}


	/**
	 * Execute a query and returns the statement object with the result
	 * 
	 * @param  string $query The Mysql query to execute
	 * @param  array $marks The marks passed to the statement
	 *
	 * @return PDOStatement The result
	 */
	public static function selectQuery ($query, array $marks = null, $fetchOne = false) {
		if (($statement = static::execute($query, $marks))) {
			$statement->setFetchMode(\PDO::FETCH_CLASS, get_called_class());

			if ($fetchOne === true) {
				return $statement->fetch();
			}

			return new ItemCollection($statement->fetchAll());
		}

		return false;
	}


	/**
	 * Makes a selection in the database
	 * 
	 * @param string $where The "where" syntax.
	 * @param array $marks Optional marks used in the query
	 * @param string $orderBy Optional parameter to sort the rows
	 * @param int/array $limit Limit of the selection. Use an array for ranges. Use true to return only the first result
	 * @param array $joins Adiccional items joined
	 * 
	 * @return object The result of the query or false if there was an error
	 */
	public static function select ($where = '', $marks = null, $orderBy = null, $limit = null, array $joins = null) {
		if ($limit === 0) {
			return new ItemCollection;
		}

		$table = static::TABLE;
		$query = '';
		$fields = "`$table`.*";

		if ($joins) {
			foreach ($joins as $join) {
				$join = static::ITEMS_NAMESPACE.$join;

				if (($relation = static::getRelation($join))) {
					list($type, $foreign_key) = $relation;

					if ($type === self::RELATION_HAS_ONE) {
						$rel_table = $join::TABLE;
						$fields .= ', '.$join::getSqlFields();

						$query .= " LEFT JOIN `$rel_table` ON (`$rel_table`.`id` = `$table`.`$foreign_key`)";

						continue;
					}
				}

				throw new \Exception('The items '.static::TABLE.' and '.$join::TABLE.' cannot be related');
			}
		}

		$query = "SELECT $fields FROM `$table`$query";

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

		return static::selectQuery($query, $marks, $limit);
	}


	/**
	 * Select one or more rows using an index key
	 * 
	 * @param Item $id A item related
	 * 
	 * @param Collection $id A item collection related
	 * 
	 * @param int/array $id The id value
	 * @param string $name The name of the id field. By default "id"
	 * @param array $join Adiccional items joined
	 * 
	 * @return object The result of the query or false if there was an error
	 */
	public static function selectBy ($id, array $join = null) {
		if ($id instanceof Item) {
			if (!($relation = static::getRelation($id))) {
				throw new \Exception('The items '.static::TABLE.' and '.$id::TABLE.' are no related');
			}

			list($type, $foreign_key) = $relation;
			$table = static::TABLE;

			switch ($type) {
				case self::RELATION_HAS_ONE:
					if (isset($id->id)) {
						return static::select("`$table`.`$foreign_key` = :id", [':id' => $id->id], null, null, $join);
					}

					return null;

				case self::RELATION_HAS_MANY:
					if (isset($id->$foreign_key)) {
						return static::select("`$table`.`id` = :id", [':id' => $id->$foreign_key], null, true, $join);
					}

					return null;
			}
		}

		if ($id instanceof ItemCollection) {
			if (!($item = $id->rewind())) {
				return new ItemCollection;
			}

			if (!($relation = static::getRelation($item))) {
				throw new \Exception('The items '.static::TABLE.' and '.$item::TABLE.' are no related');
			}

			list($type, $foreign_key) = $relation;
			$table = static::TABLE;

			switch ($type) {
				case self::RELATION_HAS_ONE:
					$ids = $id->get('id');

					if ($ids) {
						return static::select("`$table`.`$foreign_key` IN (:id)", [':id' => $ids], null, count($ids), $join);
					}

					return new ItemCollection;

				case self::RELATION_HAS_MANY:
					$ids = $id->get($foreign_key);

					if ($ids) {
						return static::select("`$table`.`id` IN (:id)", [':id' => $ids], null, count($ids), $join);
					}
					
					return new ItemCollection;
			}
		}

		if (empty($id)) {
			return is_array($id) ? new ItemCollection : false;
		}

		if (is_array($id)) {
			return static::select("id IN (:id)", [':id' => $id], null, count($id), $join);
		}

		return static::select("id = :id", [':id' => $id], null, true, $join);
	}


	/**
	 * Makes a SELECT COUNT
	 * 
	 * @param string $where The "where" syntax.
	 * @param array $marks Optional marks used in the query
	 * @param int/string $limit Limit of the selection. Use an array for ranges
	 * 
	 * @return integer
	 */
	public static function count ($where = '', $marks = null, $limit = null) {
		$table = static::TABLE;
		$query = "SELECT COUNT(*) FROM `$table`";

		if ($where) {
			$query .= " WHERE $where";
		}

		if ($limit) {
			$query .= ' LIMIT '.(is_array($limit) ? implode(', ', $limit) : $limit);
		}

		if (($statement = static::execute($query, $marks)) && ($result = $statement->fetch(\PDO::FETCH_NUM))) {
			return (int)$result[0];
		}

		return false;
	}


	/**
	 * Makes a SELECT COUNT to check if an item exists in database
	 * 
	 * @param int $id The id value
	 * @param string $name The name of the id field. By default "id"
	 * 
	 * @return boolean
	 */
	public static function exists ($id, $name = 'id') {
		return (static::count("$name = :id", [':id' => $id], 1) === 1);
	}


	/**
	 * Saves the item into the database.
	 * If the object has the property "id", makes an UPDATE, otherwise makes an INSERT
	 * 
	 * @return boolean True if the row has been saved, false if doesn't
	 */
	public function save ($applyCallback = true) {
		$table = static::TABLE;
		$data = $this->get();

		unset($data['id']);

		$new = empty($this->id);

		if (!($data = $this->prepareData($data, $new))) {
			return false;
		}

		$initialTransation = ((static::$__inTransaction === false) && (static::$__connection->inTransaction() === false));

		try {
			if ($initialTransation) {
				static::$__connection->beginTransaction();
				static::$__inTransaction = true;
			}

			//Insert
			if ($new) {
				$fields = '`'.implode('`, `', array_keys($data)).'`';
				$marks = implode(', ', array_fill(0, count($data), '?'));

				static::execute("INSERT INTO `$table` ($fields) VALUES ($marks)", array_values($data));
				$this->id = static::$__connection->lastInsertId();

				if ($applyCallback === true) {
					$this->onInsert($data);
				}
			}

			//Update
			else {
				$set = [];

				foreach ($data as $field => $value) {
					$set[] = "`$field` = ?";
				}

				$set = implode(', ', $set);
				$id = intval($this->id);

				if ($applyCallback === true) {
					if (static::ONUPDATE_PASS_DIFF_VALUES === true) {
						if (($oldValues = $this->selectBy($id))) {
							static::execute("UPDATE `$table` SET $set WHERE id = $id LIMIT 1", array_values($data));

							$this->onUpdate(array_diff_assoc($oldValues->get(), $data));
						}
					} else {
						static::execute("UPDATE `$table` SET $set WHERE id = $id LIMIT 1", array_values($data));

						$this->onUpdate($data);
					}
				} else {
					static::execute("UPDATE `$table` SET $set WHERE id = $id LIMIT 1", array_values($data));
				}
			}

			if ($initialTransation) {
				static::$__connection->commit();
				static::$__inTransaction = false;
			}

			return true;

		} catch (\Exception $e) {
			if ($initialTransation) {
				static::$__connection->rollBack();
			}

			return false;
		}
	}

	public function prepareData (array $data, $new) {
		return $data;
	}

	public function onInsert (array $data) {}
	public function onUpdate (array $data) {}
	public function onDelete () {}


	/**
	 * Deletes the current item in the database
	 * 
	 * @return boolean True if the register is deleted, false if any error happened
	 */
	public function delete ($callbackArgs = null) {
		if (empty($this->id)) {
			return false;
		}
		
		$table = static::TABLE;

		$initialTransation = ((static::$__inTransaction === false) && (static::$__connection->inTransaction() === false));

		try {
			if ($initialTransation) {
				static::$__connection->beginTransaction();
				static::$__inTransaction = true;
			}

			static::execute("DELETE FROM `$table` WHERE id = :id LIMIT 1", [':id' => $this->id]);
			
			if ($callbackArgs !== false) {
				$this->onDelete($callbackArgs);
			}

			if ($initialTransation) {
				static::$__connection->commit();
				static::$__inTransaction = false;
			}

			$this->id = null;

			return true;

		} catch (\Exception $e) {
			if ($initialTransation) {
				static::$__connection->rollBack();
			}

			return false;
		}
	}


	/**
	 * Execute a query and returns the statement object with the result
	 * 
	 * @param string $query The Mysql query to execute
	 * @param array $marks The marks passed to the statement
	 *
	 * @throws Exception On error preparing or executing the statement
	 * 
	 * @return PDOStatement The result
	 */
	private static function execute ($query, array $marks = null) {
		$query = (string)$query;

		if (!empty($marks)) {
			foreach ($marks as $name => $mark) {
				if (is_array($mark)) {
					foreach ($mark as &$val) {
						$val = static::$__connection->quote($val);
					}

					$query = str_replace($name, implode(', ', $mark), $query);
					unset($marks[$name]);
				}
			}
			if (empty($marks)) {
				$marks = null;
			}
		}

		$statement = static::$__connection->prepare($query);

		if ($statement === false) {
			throw new \Exception('MySQL error: '.implode(' / ', static::$__connection->errorInfo()));
			return false;
		}

		if ($statement->execute($marks) === false) {
			throw new \Exception('MySQL statement error: '.implode(' / ', $statement->errorInfo()));
			return false;
		}

		if (is_array(static::$__debug)) {
			static::$__debug[] = [
				'statement' => $statement,
				'marks' => $marks,
				'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20)
			];
		}

		return $statement;
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

		$foreignClass = static::ITEMS_NAMESPACE.$name;

		if (class_exists($foreignClass) && static::getRelation($foreignClass)) {
			return $this->$name = $foreignClass::selectBy($this);
		}

		return $this->$name = null;
	}

	public function load ($joinItem) {
		foreach ((array)$joinItem as $name) {
			$foreignClass = static::ITEMS_NAMESPACE.$name;

			if (class_exists($foreignClass) && static::getRelation($foreignClass)) {
				return $this->$name = $foreignClass::selectBy($this);
			}
		}
	}


	/**
	 * Magic method to execute 'set' functions and relate items each others.
	 * 
	 * @param string $name The property name
	 * @param string $value The value of the property
	 */
	public function __set ($name, $value) {
		if (strpos($name, '.')) {
			list($name, $property) = explode('.', $name, 2);

			if ($value !== null) {
				if (isset($this->$name) && ($this->$name instanceof Item)) {
					return $this->$name->$property = $value;
				}

				$foreignClass = static::ITEMS_NAMESPACE.$name;

				if (class_exists($foreignClass)) {
					return $this->$name = $foreignClass::create([$property => $value]);
				}
			}
		} else {
			$this->$name = $value;
		}
	}


	/**
	 * Edit the data of the object using an array
	 * It's the same than edit the properties of the object but only accepts defined properties
	 * 
	 * @param array $data The new data (name => value)
	 * 
	 * @param array $name The property name
	 * @param array $value The new value
	 *
	 * @return $this
	 */
	public function set ($name, $value = null) {
		$fields = getItemVars($this);

		if (is_array($name)) {
			foreach ($name as $name => $value) {
				if (is_int($name)) {
					static::set($value);
				} else {
					static::set($name, $value);
				}
			}

			return $this;
		}

		if ($name instanceof Item) {
			if ($relation = static::getRelation($name)) {
				list($type, $foreign_key) = $relation;

				if ($type === self::RELATION_HAS_ONE) {
					$this->$foreign_key = $name->id;
					$prefix = lcfirst(substr(get_class($name), strlen(static::ITEMS_NAMESPACE)));

					$this->$prefix = $name;

					return $this;
				}
			}

			throw new \Exception('The items '.static::TABLE.' and '.$name::TABLE.' cannot be related');
		}

		if (!in_array($name, $fields)) {
			throw new \Exception("The property '$name' is not defined in ".static::TABLE);
		}

		$this->$name = $value;

		return $this;
	}


	/**
	 * Returns one or all data of the item
	 *
	 * @param string $name The property name
	 * 
	 * @return mixed The property value or an array with all values
	 */
	public function get ($name = null) {
		$fields = getItemVars($this);

		if ($name !== null) {
			if (in_array($name, $fields)) {
				return $this->$fields;
			}

			throw new \Exception("The property '$name' is not defined in".static::TABLE);
		}

		$data = [];

		foreach ($fields as $name) {
			$data[$name] = $this->$name;
		}

		return $data;
	}


	/**
	 * Returns the type of relation with other item
	 * 
	 * @param  SimpleCrud\Item $item
	 * 
	 * @return array The relation type and the foreign_key used
	 */
	public static function getRelation ($item) {
		if (($foreign_key = $item::FOREIGN_KEY) && property_exists(get_called_class(), $foreign_key)) {
			return [self::RELATION_HAS_ONE, $foreign_key];
		}

		if (($foreign_key = static::FOREIGN_KEY) && property_exists($item, $foreign_key)) {
			return [self::RELATION_HAS_MANY, $foreign_key];
		}
	}
}


function getItemVars ($item) {
	if (is_object($item)) {
		$item = get_class($item);
	}

	return array_keys(get_class_vars($item));
}