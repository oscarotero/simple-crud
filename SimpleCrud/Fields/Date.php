<?php
/**
 * SimpleCrud\Fields\Field
 *
 * Base class used by all fields
 */

namespace SimpleCrud\Fields;

class Date extends Field {
	public function dataToDatabase ($data) {
		if (is_string($data)) {
			return date('Y-m-d', strtotime($data));
		}

		if ($data instanceof \Datetime) {
			return $data->format('Y-m-d');
		}
	}
}
