<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries;

use SimpleCrud\Table;

final class Update extends Query
{
    use Traits\HasRelatedWith;

    protected const ALLOWED_METHODS = [
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
