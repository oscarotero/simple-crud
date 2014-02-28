<?php
/**
 * SimpleCrud\EntityGenerator
 *
 * Generate entities classes
 */

namespace SimpleCrud;


class EntityGenerator {
	protected $pdo;
	protected $path;
	protected $namespace;

	public function __construct (\PDO $pdo, $path, $namespace) {
		$this->pdo = $pdo;
		$this->path = $path;
		$this->namespace = $namespace;

		if (!is_dir($this->path)) {
			mkdir($this->path, 0777, true);
		}
	}


	/**
	 * Returns all tables of the database
	 *
	 * @return array The table names
	 */
	private function getTables () {
		return $this->pdo->query('SHOW TABLES', \PDO::FETCH_COLUMN, 0)->fetchAll();
	}


	/**
	 * Returns a list of all fields in a table
	 *
	 * @param string $table The table name
	 *
	 * @return array The fields info
	 */
	private function getFields ($table) {
		return $this->pdo->query("DESCRIBE `$table`")->fetchAll();
	}


	public function generate () {
		foreach ($this->getTables() as $table) {
			$this->generateEntity($table);
		}
	}

	public function generateEntity ($table) {
		$namespace = $this->namespace ? "namespace {$this->namespace};\n" : '';
		$className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
		$fields = '';

		foreach ($this->getFields($table) as $field) {
			preg_match('#^(\w+)#', $field['Type'], $matches);

			$fields .= "\n\t\t'".$field['Field']."' => '".(class_exists('SimpleCrud\\Fields\\'.ucfirst($matches[1])) ? $matches[1] : 'field')."',";
		}

		$code = <<<EOT
<?php
{$namespace}
class {$className} extends \SimpleCrud\Entity {
	public \$table = '{$table}';
	public \$foreignKey = '{$table}_id';
	public \$fields = [{$fields}
	];
}

class {$className}Row extends \SimpleCrud\Row {
}

class {$className}RowCollection extends \SimpleCrud\RowCollection {	
}

EOT;
		file_put_contents("{$this->path}/{$className}.php", $code);
	}
}



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