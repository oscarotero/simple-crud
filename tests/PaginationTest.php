<?php
namespace SimpleCrud\Tests;

use DateTime;
use SimpleCrud\Row;

class PaginationTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT
);
EOT
        ]);
    }

    public function testPagination()
    {
        $db = $this->createDatabase();

        for ($n = 0; $n < 201; $n++) {
            $db->post[] = ['title' => "Post number {$n}"];
        }

        $query = $db->post->select()
            ->page(1)
            ->perPage(20);
        $pagination = $query->getPageInfo();

        $this->assertSame(201, $pagination['totalRows']);
        $this->assertSame(11, $pagination['totalPages']);
        $this->assertSame(1, $pagination['currentPage']);
        $this->assertSame(2, $pagination['nextPage']);
        $this->assertSame(null, $pagination['previousPage']);

        $query = $db->post->select()
            ->page(1)
            ->perPage(20)
            ->where('id IS NULL');
        $pagination = $query->getPageInfo();

        $this->assertSame(0, $pagination['totalRows']);
        $this->assertSame(0, $pagination['totalPages']);
        $this->assertSame(null, $pagination['currentPage']);
        $this->assertSame(null, $pagination['nextPage']);
        $this->assertSame(null, $pagination['previousPage']);
    }
}
