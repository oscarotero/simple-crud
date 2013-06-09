<?php
/**
 * SimpleCrud\Item
 * 
 * Provides a simple model with basic database operations.
 */
namespace SimpleCrud;

class Item {
	public static $connection;
	public static $debug = false;


	/**
	 * Set the database connection.
	 * 
	 * @param PDO $Db The database object
	 */
	public static function setConnection (\PDO $Connection) {
		static::$connection = $Connection;
	}


	/**
	 * Returns the names of the fields in the database
	 * 
	 * @return array The fields name
	 */
	public static function getFields () {
		if (empty(static::$fields)) {
			$table = static::$table;

			static::$fields = static::$connection->query("DESCRIBE `$table`", \PDO::FETCH_COLUMN, 0)->fetchAll();
		}

		return static::$fields;
	}


	/**
	 * returns the fields ready to use in a mysql query
	 * This function is useful to "import" a model inside another, you just have to include the fields names of the model.
	 * 
	 * Example:
	 * $fieldsQuery = User::getQueryFields();
	 * $posts = Post::fetchAll("SELECT posts.*, $fieldsQuery FROM posts, users WHERE posts.author = users.id");
	 * $posts[0]->User //The user model inside the post
	 * 
	 * @param string $name The name of the parameter used to the sub-model. If it's not defined, uses the model class name (without the namespace)
	 * @param array $filter The names of the fields to return. If it's not defined, returns all
	 * 
	 * @return string The portion of mysql code with the fields names
	 */
	public static function getQueryFields ($name = null, array $filter = null) {
		$table = static::$table;
		$fields = array();
		$class = get_called_class();

		if ($name === null) {
			$name = (($pos = strrpos($class, '\\')) === false) ? $class : substr($class, $pos + 1);
			$name = lcfirst($name);
		}

		if ($filter === null) {
			foreach (static::getFields() as $field) {
				$fields[] = "`$table`.`$field` as `$class::$field::$name`";
			}
		} else {
			foreach (static::getFields() as $field) {
				if (in_array($field, $filter)) {
					$fields[] = "`$table`.`$field` as `$class::$field::$name`";
				}
			}
		}

		return implode(', ', $fields);
	}


	/**
	 * Constructor
	 */
	public function __construct () {
		$this->init();
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

		return $this->$name = null;
	}


	/**
	 * Magic method to execute 'set' functions.
	 * 
	 * @param string $name The property name
	 * @param string $value The value of the property
	 */
	public function __set ($name, $value) {
		$method = "set$name";

		if (method_exists($this, $method)) {
			$this->$method($value);
		} else {
			$this->$name = $value;
		}
	}



	/**
	 * Initialize the values, resolve fields.
	 */
	public function init () {
		$fields = array();

		foreach ($this as $key => $value) {
			if (strpos($key, '::') !== false) {
				list($class, $field, $name) = explode('::', $key, 3);

				if (!isset($this->$name)) {
					$fields[] = $name;
					$this->$name = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
				}

				$this->$name->$field = $value;
				unset($this->$key);
			}
		}

		foreach ($fields as $name) {
			$this->$name->__construct();
		}
	}



	/**
	 * Execute a query and returns the statement object with the result
	 * 
	 * @param  string $query The Mysql query to execute
	 * @param  array $marks The marks passed to the statement
	 *
	 * @throws Exception On error preparing or executing the statement
	 * 
	 * @return PDOStatement The result
	 */
	public static function execute ($query, array $marks = null) {
		$statement = static::$connection->prepare($query);

		if ($statement === false) {
			throw new \Exception('MySQL error: '.implode(' / ', static::$connection->errorInfo()));
			return false;
		}

		if ($statement->execute($marks) === false) {
			throw new \Exception('MySQL statement error: '.implode(' / ', $statement->errorInfo()));
			return false;
		}

		if (is_array(static::$debug)) {
			static::debug($statement, $marks);
		}

		return $statement;
	}


	/**
	 * Save the current statement for debuggin purposes
	 * 
	 * @param  PDOStatement $statement The query statement
	 * @param  array $marks The marks passed to the statement
	 */
	public static function debug (\PDOStatement $statement, array $marks = null) {
		static::$debug[] = [
			'statement' => $statement,
			'marks' => $marks,
			'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20)
		];
	}



	/**
	 * Fetch all results of a mysql selection
	 * 
	 * @param string/PDOStatement $query The query for the selection
	 * @param array $marks Optional marks used in the query
	 * 
	 * @return array The result of the query or false if there was an error
	 */
	public static function fetchAll ($query, array $marks = null) {
		if (!($query instanceof \PDOStatement)) {
			$query = static::execute($query, $marks);
		}

		$result = $query->fetchAll(\PDO::FETCH_CLASS, get_called_class());

		return new ItemCollection($result);
	}


	/**
	 * Fetch the first result of a mysql selection
	 * 
	 * @param string/PDOStatement $query The query for the selection. Note that "LIMIT 1" will be automatically added
	 * @param array $marks Optional marks used in the query
	 * 
	 * @return object The result of the query or false if there was an error
	 */
	public static function fetch ($query, array $marks = null) {
		if (!($query instanceof \PDOStatement)) {
			$query = static::execute($query, $marks);
		}

		$query->setFetchMode(\PDO::FETCH_CLASS, get_called_class());

		return $query->fetch();
	}


	/**
	 * Select one or more rows using a index key
	 * 
	 * @param int/array $id The id value
	 * @param string $name The name of the id field. By default "id"
	 * 
	 * @return object The result of the query or false if there was an error
	 */
	public static function selectBy ($id, $name = 'id') {
		if ($id instanceof Item) {
			$Item = $id;

			if (!empty($Item::$relation_field)) {
				$field = $Item::$relation_field;

				if (in_array($field, static::getFields())) {
					if (!isset($Item->id)) {
						throw new \Exception('The item '.$Item::$table.' must have defined the property "id" to select the related items in '.static::$table);
					}

					return static::selectAll("$field = :id", [':id' => $Item->id]);
				}
			}

			if (!empty(static::$relation_field)) {
				$field = static::$relation_field;

				if (in_array($field, $Item->getFields())) {
					if (!isset($Item->$field)) {
						throw new \Exception('The item '.$Item::$table.' must have defined the property "'.$field.'" to select the related items in '.static::$table);
					}

					return static::selectOne('id = :id', [':id' => $Item->$field]);
				}
			}

			throw new \Exception('The items '.static::$table.' and '.$Item::$table.' are no related');
		}

		if ($id instanceof ItemCollection) {
			if ($id->isEmpty()) {
				return new ItemCollection;
			}

			$Item = $id->rewind();

			if (!empty($Item::$relation_field) && ($field = $Item::$relation_field) && in_array($field, static::getFields())) {
				$id = $id->getKeys();
				$name = $field;
			} else if (!empty(static::$relation_field) && ($field = static::$relation_field) && in_array($field, $Item::getFields())) {
				$id = $id->getKeys($field);
			} else {
				throw new \Exception('The items '.static::$table.' and '.$Item::$table.' are no related');
			}
		}

		if (empty($id)) {
			return is_array($id) ? new ItemCollection : false;
		}

		if (is_array($id)) {
			$limit = count($id);
			$in = substr(str_repeat(', ?', $limit), 2);

			return static::selectAll("$name IN ($in)", array_values($id), null, $limit);
		}

		return static::selectOne("$name = :id", [':id' => $id]);
	}


	/**
	 * Select all rows using custom conditions
	 * 
	 * Example:
	 * $Item = Item::selectOne('title = :title', [':title' => 'Titulo']);
	 * 
	 * @param string $where The "where" syntax.
	 * @param array $marks Optional marks used in the query
	 * @param string $orderBy Optional parameter to sort the rows
	 * @param int/array $limit Limit of the selection. Use an array for ranges
	 * 
	 * @return object The result of the query or false if there was an error
	 */
	public static function selectAll ($where = '', $marks = null, $orderBy = null, $limit = null) {
		$table = static::$table;

		if ($where) {
			$where = " WHERE $where";
		}

		if ($orderBy) {
			$where .= " ORDER BY $orderBy";
		}

		if ($limit) {
			$where .= " LIMIT $limit";
		}

		return static::fetchAll("SELECT * FROM `$table`$where", $marks);
	}


	/**
	 * Select one or various rows using custom conditions
	 * 
	 * Example:
	 * $Item = Item::selectOne('title = :title', [':title' => 'Titulo']);
	 * 
	 * @param string $where The "where" syntax.
	 * @param array $marks Optional marks used in the query
	 * @param string $orderBy Optional parameter to sort the rows
	 * 
	 * @return object The result of the query or false if there was an error
	 */
	public static function selectOne ($where = '', $marks = null, $orderBy = null) {
		$table = static::$table;

		if ($where) {
			$where = " WHERE $where";
		}

		if ($orderBy) {
			$where .= " ORDER BY $orderBy";
		}

		return static::fetch("SELECT * FROM `$table`$where LIMIT 1", $marks);
	}


	/**
	 * Creates a empty object or, optionally, fill with some data
	 * 
	 * @param array $data Data to fill the option.
	 * 
	 * @return object The instantiated objec
	 */
	public static function create (array $data = null) {
		$Item = new static();

		if ($data !== null) {
			$Item->set($data);
		}

		return $Item;
	}


	/**
	 * Edit the data of the object using an array (but doesn't save it into the database)
	 * It's the same than edit the properties of the object but check if the property name is in the fields list
	 * 
	 * @param array $data The new data (field => value)
	 * 
	 * @param array $field The field name
	 * @param array $value The new value
	 */
	public function set ($field, $value = null) {
		$fields = static::getFields();

		if (is_array($field)) {
			foreach ($field as $field => $value) {
				if (!in_array($field, $fields)) {
					throw new \Exception("The field '$field' does not exists");
				}

				$this->$field = $value;
			}
		} else {
			if (!in_array($field, $fields)) {
				throw new \Exception("The field '$field' does not exists");
			}

			$this->$field = $value;
		}
	}


	/**
	 * Returns one or all data of the row
	 * 
	 * @return mixed The data of the row
	 */
	public function get ($field = null) {
		if ($field !== null) {
			return (in_array($field, static::getFields()) && isset($this->$field)) ? $this->$field : null;
		}

		$data = array();

		foreach (static::getFields() as $field) {
			$data[$field] = isset($this->$field) ? $this->$field : null;
		}

		return $data;
	}


	/**
	 * Saves the object data into the database. If the object has the property "id", makes an UPDATE, otherwise makes an INSERT
	 * 
	 * @return boolean True if the row has been saved, false if doesn't
	 */
	public function save () {
		if (($data = $this->prepareToSave($this->get())) === false) {
			return false;
		}

		unset($data['id']);

		foreach ($data as $field => $value) {
			if ($value === null) {
				unset($data[$field]);
			}
		}

		$table = static::$table;

		//Insert
		if (empty($this->id)) {
			$fields = '`'.implode('`, `', array_keys($data)).'`';
			$marks = implode(', ', array_fill(0, count($data), '?'));

			if (static::execute("INSERT INTO `$table` ($fields) VALUES ($marks)", array_values($data))) {
				$this->id = static::$connection->lastInsertId();

				return true;
			}

			return false;
		}

		//Update
		$set = array();
		$id = intval($this->id);

		foreach ($data as $field => $value) {
			$set[] = "`$field` = ?";
		}

		$set = implode(', ', $set);

		return static::execute("UPDATE `$table` SET $set WHERE id = $id LIMIT 1", array_values($data)) ? true : false;
	}


	/**
	 * Deletes the current row in the database (but keep the data in the object)
	 * 
	 * @return boolean True if the register is deleted, false if any error happened
	 */
	public function delete () {
		if (!empty($this->id)) {
			$table = static::$table;
			$id = intval($this->id);

			if (static::execute("DELETE FROM `$table` WHERE id = $id LIMIT 1") !== false) {
				$this->id = null;

				return true;
			}
		}

		return false;
	}


	/**
	 * Prepare the data before to save. Useful to validate or transform data before save in database
	 * This function is provided to be overwrited by the class that uses this trait
	 * 
	 * @param array $data The data to save.
	 * 
	 * @return array The transformed data. If returns false, the data will be not saved.
	 */
	public function prepareToSave (array $data) {
		return $data;
	}


	/**
	 * Join two objects modifing the relation field
	 * 
	 * @param string $name The name used to store the item inside
	 * @param Opentraits\Crud\Item $Item The item to relate
	 *
	 * @throws Exception If the items are not related
	 *
	 * @return boolean True if the relation has been executed
	 */
	public function join ($name, Item $Item) {
		if (!empty($Item::$relation_field)) {
			$field = $Item::$relation_field;

			if (in_array($field, static::getFields())) {
				$this->$field = $Item->id;
				$this->$name = $Item;

				return true;
			}
		}

		throw new \Exception('The items '.static::$table.' and '.$Item::$table.' cannot be related');
	}
}
