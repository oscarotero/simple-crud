<?php
namespace CustomEntities {
	class CustomField extends \SimpleCrud\Entity {
		public $fields = [
			'id',
			'field' => 'json'
		];
	}
}

namespace CustomEntities\Fields {
	class Json extends \SimpleCrud\Fields\Field {
		public function dataToDatabase ($data) {
			if (is_string($data)) {
				return $data;
			}

			return json_encode($data);
		}

		public function dataFromDatabase ($data) {
			if ($data) {
				return json_decode($data, true);
			}
		}
	}
}