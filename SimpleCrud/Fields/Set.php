<?php
/**
 * SimpleCrud\Fields\Field
 *
 * Base class used by all fields
 */

namespace SimpleCrud\Fields;

class Set extends Field {
	public function dataToDatabase ($data) {
		if (is_array($data)) {
			return implode(',', $data);
		}

		return $data;
	}

	public function dataFromDatabase ($data) {
		return explode(',', $data);
	}
}
