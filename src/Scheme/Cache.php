<?php
declare(strict_types = 1);

namespace SimpleCrud\Scheme;

/**
 * Class to cache the scheme
 */
final class Cache implements SchemeInterface
{
    private $scheme;

    public static function schemeToArray(SchemeInterface $scheme): array
    {
        $arrayScheme = [];

        foreach ($scheme->getTables() as $table) {
            $arrayScheme[$table] = $scheme->getTableFields($table);
        }

        return $arrayScheme;
    }

    public function __construct(array $scheme)
    {
        $this->scheme = $scheme;
    }

    public function getTables(): array
    {
        return array_keys($this->scheme);
    }

    public function getTableFields(string $table): array
    {
        return $this->scheme[$table];
    }
}
