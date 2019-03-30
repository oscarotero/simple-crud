<?php
declare(strict_types = 1);

namespace SimpleCrud\Scheme;

use PDO;

/**
 * Class to autodetect the scheme
 */
final class Scheme implements SchemeInterface
{
    private $scheme;

    public function __construct(PDO $pdo)
    {
        $engine = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        switch ($engine) {
            case 'mysql':
                $this->scheme = new Mysql($pdo);
                break;
            case 'sqlite':
                $this->scheme = new Sqlite($pdo);
                break;
            default:
                throw new RuntimeException(sprintf('Invalid engine type: %s', $engine));
        }
    }

    public function getTables(): array
    {
        return $this->scheme->getTables();
    }

    public function getTableFields(string $table): array
    {
        return $this->scheme->getTableFields($table);
    }
}
