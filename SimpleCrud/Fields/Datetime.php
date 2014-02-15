<?php
/**
 * SimpleCrud\Fields\Field
 *
 * Base class used by all fields
 */

namespace SimpleCrud\Fields;

class Datetime extends Field {
	public function dataToDatabase ($data) {
		if (is_string($data)) {
			return date('Y-m-d H:i:s', strtotime($data));
		}

		if ($data instanceof \Datetime) {
			return $data->format('Y-m-d H:i:s');
		}
	}
}
