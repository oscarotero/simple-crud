<?php
/**
 * SimpleCrud\Fields\Field
 *
 * Base class used by all fields
 */

namespace SimpleCrud\Fields;

use SimpleCrud\Entity;

class Field {
	protected $entityName;
	protected $table;
	protected $name;

	public function __construct (Entity $entity, $name) {
		$this->entityName = $entity->name;
		$this->table = $entity->table;
		$this->name = $name;
	}

	final public function getEscapedNameForSelect () {
		return "`{$this->table}`.`{$this->name}`";
	}

	final public function getEscapedNameForJoin () {
		return "`{$this->table}`.`{$this->name}` as `{$this->entityName}.{$this->name}`";
	}

	public function dataToDatabase ($data) {
		return $data;
	}

	public function dataFromDatabase ($data) {
		return $data;
	}
}