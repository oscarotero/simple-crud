<?php
declare(strict_types = 1);

namespace SimpleCrud\Scheme;

/**
 * Class to cache the scheme
 */
final class Cache implements SchemeInterface
{
    private $scheme;

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

    public function toArray(): array
    {
        return $this->scheme;
    }
}
