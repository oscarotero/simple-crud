<?php
/**
 * OpenTraits\Crud\Cache
 * 
 * Provides a simple cache system.
 * Example:
 * 
 * class MyMethod {
 * 	use OpenTraits\Crud\Cache;
 * 
 * 	public function getColors () {
 * 		return static::connection->query('SELECT * FROM colors');
 * 	}
 * }
 * 
 * $Item = new MyMethod;
 * $Item->colors; //Execute the method "getColors" and save the result in the property colors
 */
namespace OpenTraits\Crud;

trait Cache {
	public function __getCache ($name) {
		if (isset($this->$name)) {
			return $this->$name;
		}

		$method = "get$name";

		if (method_exists($this, $method)) {
			return $this->$name = $this->$method();
		}

		return $this->$name = null;
	}


	/**
	 * Magic method to execute 'get' functions and save the result in a property.
	 * 
	 * @param string $name The property name
	 */
	public function __get ($name) {
		return $this->__getCache($name);
	}
}
?>