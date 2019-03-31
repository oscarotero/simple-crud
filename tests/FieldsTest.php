<?php
namespace SimpleCrud\Tests;

use Datetime;
use SimpleCrud\Fields\Json;

class FieldsTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `isActive`    INTEGER,
    `publishedAt` TEXT,
    `data`        TEXT
);
EOT
        ]);
    }

    public function testFields()
    {
        $db = $this->createDatabase();
        $db->getFieldFactory()->defineField(Json::class, ['names' => ['data']]);

        $this->assertInstanceOf(Json::class, $db->post->data);

        $now = new Datetime();

        $db->post->insert([
            'title' => 'First post',
            'publishedAt' => $now,
            'data' => [
                'foo' => 'bar',
                'bar' => [1, 2, 3],
            ],
        ])->run();

        $post = $db->post[1];

        $this->assertInstanceOf(Datetime::class, $post->publishedAt);
        $this->assertNotSame($now, $post->publishedAt);
        $this->assertEquals($now->format('Y-m-d h:i:s'), $post->publishedAt->format('Y-m-d h:i:s'));
        $this->assertIsInt($post->id);
        $this->assertIsArray($post->data);
        $this->assertIsBool($post->isActive);
    }
}
