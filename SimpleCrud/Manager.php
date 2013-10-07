<?php
/**
 * SimpleCrud\Manager
 * 
 * Provides a simple model with basic database operations.
 */
namespace SimpleCrud;

class Manager {
	protected $connection;
	protected $inTransaction;
	protected $namespace;
	protected $entities = [];
	protected $debug = [];


	public function __construct (\PDO $connection, $namespace = '') {
		$this->connection = $connection;

		if ($namespace && (substr($namespace, -1) !== '\\')) {
			$namespace .= '\\';
		}

		$this->namespace = $namespace;
	}


	public function __get ($name) {
		if (isset($this->entities[$name])) {
			return $this->entities[$name];
		}

		$entityClass = $this->namespace.ucfirst($name);

		return $this->entities[$name] = new $entityClass($this, $this->namespace.ucfirst($name));
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
	public function execute ($query, array $marks = null) {
		$query = (string)$query;

		if (!empty($marks)) {
			foreach ($marks as $name => $mark) {
				if (is_array($mark)) {
					foreach ($mark as &$val) {
						$val = $this->connection->quote($val);
					}

					$query = str_replace($name, implode(', ', $mark), $query);
					unset($marks[$name]);
				}
			}
			if (empty($marks)) {
				$marks = null;
			}
		}

		try {
			$statement = $this->connection->prepare($query);

			if ($statement === false) {
				throw new \Exception('MySQL error: '.implode(' / ', $this->connection->errorInfo()));
			}

			if ($statement->execute($marks) === false) {
				throw new \Exception('MySQL statement error: '.implode(' / ', $statement->errorInfo()));
			}
		} catch (\Exception $exception) {
			if (is_array($this->debug)) {
				$this->debug[] = [
					'error' => $exception->getMessage(),
					'statement' => $statement,
					'marks' => $marks,
					'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20)
				];
			}

			throw $exception;
		}

		if (is_array($this->debug)) {
			$this->debug[] = [
				'statement' => $statement,
				'marks' => $marks,
				'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20)
			];
		}

		return $statement;
	}


	public function lastInsertId () {
		return $this->connection->lastInsertId();
	}


	public function beginTransaction () {
		if (($this->inTransaction === false) && ($this->connection->inTransaction() === false)) {
			$this->connection->beginTransaction();
			return $this->inTransaction = true;
		}

		return false;
	}

	public function commit () {
		if (($this->inTransaction === true) && ($this->connection->inTransaction() === true)) {
			$this->connection->commit();
			$this->inTransaction = false;
		}
	}

	public function rollBack () {
		if (($this->inTransaction === true) && ($this->connection->inTransaction() === true)) {
			$this->connection->rollBack();
			$this->inTransaction = false;
		}
	}

	public function quote ($data) {
		if (is_array($data)) {
			foreach ($data as $name => &$value) {
				$value = $this->connection->quote($value);
			}

			return $data;
		}

		return $this->connection->quote($data);
	}


	/**
	 * Enable, disable, get debugs
	 *
	 * @param bool Enable or disable. Null to get the debug result
	 * 
	 * @return array or null
	 */
	public function debug ($enabled = null) {
		$debug = $this->debug;

		if ($enabled === true) {
			$this->debug = is_array($this->debug) ? $this->debug : [];
		} else if ($enabled === false) {
			$this->debug = false;
		}

		return $debug;
	}
}
