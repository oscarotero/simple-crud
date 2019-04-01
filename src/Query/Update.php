<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use SimpleCrud\Table;

final class Update implements QueryInterface
{
    use Traits\Common;
    use Traits\HasRelatedWith;

    private $allowedMethods = [
        'set',
        'setFlag',
        'where',
        'orWhere',
        'catWhere',
        'orderBy',
        'limit',
        'offset',
    ];

    public function __construct(Table $table, array $data = [])
    {
        $this->table = $table;

        $this->query = $table->getDatabase()
            ->update()
            ->table((string) $table);

        foreach ($data as $fieldName => $value) {
            $this->table->{$fieldName}->update($this->query, $value);
        }
    }
}
