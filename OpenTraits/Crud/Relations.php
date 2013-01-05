<?php
/**
 * OpenTraits\Crud\Relations
 * 
 * Provides a simple model with basic relations.
 * Example:
 * 
 * class Items {
 * 	use OpenTraits\Crud\Relations;
 * }
 * class Comments {
 * 	use OpenTraits\Crud\Relations;
 * }
 * 
 * Items::setDb($Pdo);
 * 
 * $Item = Items::selectById(4);
 * $Comment = Comments::selectById(3);
 *
 * $Comment->relate($Item);
 * 
 * $Comment->save();
 */
namespace OpenTraits\Crud;

trait Relations {
	/**
	 * Static function to configure the model.
	 * Define the database, the table name and the available fields
	 * 
	 * @param PDO $Db The database object
	 * @param string $table The table name used in this model (if it not defined, use the class name)
	 * @param array $fields The name of all fields of the table. If it's not defined, execute a DESCRIBE query
	 */
	public function relate ($Item) {
		if (isset($Item::$relation_field)) {
			$field = $Item::$relation_field;

			if (property_exists($this, $field)) {
				$this->$field = $Item->id;
			}
		}

		if (isset(static::$relation_field)) {
			$field = static::$relation_field;

			if (property_exists($Item, $field)) {
				$Item->$field = $this->id;
			}
		}
	}


	public function unrelate ($Item) {
		$field = $Item::$relation_field;

		if (property_exists($this, $field)) {
			$this->$field = 0;
		}

		$field = static::$relation_field;

		if (property_exists($Item, $field)) {
			$Item->$field = 0;
		}
	}
}
?>