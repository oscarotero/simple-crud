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
	 * Relate two objects
	 * 
	 * @param Object $Item The item to relate
	 * @param boolean $save Set true to execute the save method just before relate
	 */
	public function relate ($Item, $save = false) {
		if (!empty($Item::$relation_field)) {
			$field = $Item::$relation_field;

			if (property_exists($this, $field) && ($this->$field !== $Item->id)) {
				$this->$field = $Item->id;

				if ($save === true) {
					$this->save();
				}
			}
		}

		if (!empty(static::$relation_field)) {
			$field = static::$relation_field;

			if (property_exists($Item, $field) && ($Item->$field !== $this->id)) {
				$Item->$field = $this->id;

				if ($save === true) {
					$Item->save();
				}
			}
		}
	}


	/**
	 * Unrelate two objects
	 * 
	 * @param Object $Item The item to unrelate
	 * @param boolean $save Set true to execute the save method just before unrelate
	 */
	public function unrelate ($Item, $save = false) {
		if (!empty($Item::$relation_field)) {
			$field = $Item::$relation_field;

			if (property_exists($this, $field) && ($this->$field !== 0)) {
				$this->$field = 0;

				if ($save === true) {
					$this->save();
				}
			}
		}

		if (!empty(static::$relation_field)) {
			$field = static::$relation_field;

			if (property_exists($Item, $field) && ($Item->$field !== 0)) {
				$Item->$field = 0;

				if ($save === true) {
					$Item->save();
				}
			}
		}
	}
}
?>