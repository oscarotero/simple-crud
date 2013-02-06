<?php
/**
 * OpenTraits\Crud\Mysql
 * 
 * Provides a simple model with basic database operations.
 * Example:
 * 
 * class Items {
 *  use OpenTraits\Crud\Mysql;
 * 
 *  static $table = 'items';
 *  static $fields = null;
 * }
 * 
 * Items::setConnection($Pdo);
 * 
 * $Item = Items::create(array(
 * 	'name' => 'Item name',
 * 	'description' => 'Item description'
 * ));
 * 
 * $Item->save();
 * $Item->name = 'New name for the item';
 * $Item->save();
 */
namespace OpenTraits\Crud;

trait Mysql {
	public static $connection;
	public static $error = null;


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
	 * static function to convert a select query from array to string
	 * 
	 * @return string The generated query
	 */
	static private function generateSelectQuery (array $query) {
		$string = 'SELECT '.implode(', ', (array)$query['select']);
		$string .= ' FROM '.implode(', ', (array)$query['from']);

		if (!empty($query['left-join'])) {
			$query['left-join'] = (array)$query['left-join'];

			if (!isset($query['left-join'][0])) {
				$query['left-join'] = [$query['left-join']];
			}

			foreach ($query['left-join'] as $value) {
				$string .= ' LEFT JOIN '.(is_array($value) ? static::generateSelectQuery($value) : $value);
			}
		}
		if (!empty($query['inner-join'])) {
			$string .= ' INNER JOIN '.implode(', ', (array)$query['inner-join']);
		}
		if (!empty($query['where'])) {
			$string .= ' WHERE ('.implode(' AND ', (array)$query['where']).')';
		}
		if (!empty($query['union'])) {
			if (!isset($query['union'][0])) {
				$query['union'] = [$query['union']];
			}

			foreach ($query['union'] as $value) {
				$string .= ' UNION '.(is_array($value) ? static::generateSelectQuery($value) : $value);
			}
		}
		if (!empty($query['union-all'])) {
			if (!isset($query['union-all'][0])) {
				$query['union-all'] = [$query['union-all']];
			}

			foreach ($query['union-all'] as $value) {
				$string .= ' UNION ALL '.(is_array($value) ? static::generateSelectQuery($value) : $value);
			}
		}
		if (!empty($query['group-by'])) {
			$string .= ' GROUP BY '.implode(', ', (array)$query['group-by']);
		}
		if (!empty($query['order-by'])) {
			$string .= ' ORDER BY '.implode(', ', (array)$query['order-by']);
		}
		if (!empty($query['limit'])) {
			$string .= ' LIMIT '.implode(', ', (array)$query['limit']);
		}

		return trim($string);
	}


	/**
	 * returns the fields ready to use in a mysql query
	 * This function is useful to "import" a model inside another, you just have to include the fields names of the model.
	 * 
	 * Example:
	 * $fieldsQuery = User::getQueryFields();
	 * $posts = Post::select("SELECT posts.*, $fieldsQuery FROM posts, users WHERE posts.author = users.id");
	 * $posts[0]->User //The user model inside the post
	 * 
	 * @param string $name The name of the parameter used to the sub-model. If it's not defined, uses the model class name (without the namespace)
	 * 
	 * @return string The portion of mysql code with the fields names
	 */
	public static function getQueryFields ($name = null) {
		$table = static::$table;
		$fields = array();
		$class = get_called_class();

		if ($name === null) {
			$name = (($pos = strrpos($class, '\\')) === false) ? $class : substr($class, $pos + 1);
			$name = lcfirst($name);
		}

		foreach (static::getFields() as $field) {
			$fields[] = "`$table`.`$field` as `$class::$field::$name`";
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
	 * Initialize the values, resolve fields.
	 */
	public function init () {
		foreach (static::getFields() as $field) {
			if (!isset($this->$field)) {
				$this->$field = null;
			}
		}

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


	public static function query ($query, array $marks = null) {
		$statement = static::$connection->prepare($query);

		if ($statement === false) {
			static::$error = static::$connection->errorInfo();

			return false;
		}

		if ($statement->execute($marks) === false) {
			static::$error = $statement->errorInfo();

			return false;
		}

		return $statement;
	}


	/**
	 * Execute a selection and returns an array with the models result
	 * 
	 * @param array $query The query for the selection
	 * @param array $marks Optional marks used in the query
	 * 
	 * @return array The result of the query or false if there was an error
	 */
	public static function select (array $query = array(), array $marks = null) {
		$table = static::$table;

		$query = static::generateSelectQuery(array_replace([
			'select' => ["$table.*"],
			'from' => [$table]
		], $query));

		return static::selectByQuery($query, $marks);
	}


	/**
	 * Execute a selection using a query string and returns an array with the models result
	 * 
	 * @param string $query The query for the selection
	 * @param array $marks Optional marks used in the query
	 * 
	 * @return array The result of the query or false if there was an error
	 */
	public static function selectByQuery ($query, array $marks = null) {
		if ($statement = static::query($query, $marks)) {
			return $statement->fetchAll(\PDO::FETCH_CLASS, get_called_class());
		}

		return false;
	}


	/**
	 * Execute a selection of just one element.
	 * 
	 * Example:
	 * $Item = Item::selectOne('WHERE title = :title', array(':title' => 'My item title'))
	 * 
	 * @param array $query The query for the selection. Note that "LIMIT 1" will be automatically added
	 * @param array $marks Optional marks used in the query
	 * 
	 * @return object The result of the query or false if there was an error
	 */
	public static function selectOne (array $query = array(), array $marks = null) {
		if (!isset($query['limit'])) {
			$query['limit'] = 1;
		}

		return current(static::select($query, $marks));
	}


	/**
	 * Shortcut to select a row by id
	 * 
	 * Example:
	 * $Item = Item::selectById(45);
	 * 
	 * @param int $id The row id.
	 * 
	 * @return object The result of the query or false if there was an error
	 */
	public static function selectById ($id) {
		return static::selectOne(['where' => 'id = :id'], [':id' => $id]);
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
			$Item->edit($data);
		}

		return $Item;
	}


	/**
	 * Edit the data of the object using an array (but doesn't save it into the database)
	 * It's the same than edit the properties of the object but check if the property name is in the fields list
	 * 
	 * @param array $data The new data
	 */
	public function edit (array $data) {
		$fields = static::getFields();

		foreach ($data as $field => $value) {
			if (!in_array($field, $fields)) {
				throw new \Exception("The field '$field' does not exists");
			}

			$this->$field = $value;
		}
	}


	/**
	 * Deletes the properties of the model (but not in the database)
	 */
	public function clean () {
		foreach (static::getFields() as $field) {
			$this->$field = null;
		}
	}


	/**
	 * Saves the object data into the database. If the object has the property "id", makes an UPDATE, otherwise makes an INSERT
	 * 
	 * @return boolean True if the row has been saved, false if doesn't
	 */
	public function save () {
		if (($data = $this->prepareToSave($this->getData())) === false) {
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
			$fields = implode(', ', array_keys($data));
			$marks = implode(', ', array_fill(0, count($data), '?'));

			if (static::query("INSERT INTO `$table` ($fields) VALUES ($marks)", array_values($data))) {
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

		return static::query("UPDATE `$table` SET $set WHERE id = $id LIMIT 1", array_values($data)) ? true : false;
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

			if (static::query("DELETE FROM `$table` WHERE id = $id LIMIT 1") !== false) {
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
	 * Returns the fields data of the row
	 * 
	 * @return array The data of the row
	 */
	public function getData () {
		$data = array();

		foreach (static::getFields() as $field) {
			$data[$field] = isset($this->$field) ? $this->$field : null;
		}

		return $data;
	}


	/**
	 * Get the latest error
	 * 
	 * @param mixed $error The error message
	 */
	public function getError () {
		return static::$error;
	}
}
?>