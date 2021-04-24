<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries;

use SimpleCrud\Table;

/**
 * @method void setFlag(string $flag, bool $enable = true)
 * @method self where(string $condition, ...$bindInline)
 * @method self orWhere(string $condition, ...$bindInline)
 * @method self catWhere(string $condition, ...$bindInline)
 * @method self orderBy(string $expr, string ...$exprs)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 */
final class Delete extends Query
{
    use Traits\HasRelatedWith;

    protected const ALLOWED_METHODS = [
        'setFlag',
        'where',
        'orWhere',
        'catWhere',
        'orderBy',
        'limit',
        'offset',
    ];

    public function __construct(Table $table)
    {
        $this->table = $table;

        $this->query = $table->getDatabase()
            ->delete()
            ->from((string) $table);
    }
}
