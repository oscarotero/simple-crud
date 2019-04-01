<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Row;

class TableTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `isActive`    INTEGER
);
EOT
        ]);
    }

    public function testTable()
    {
        $db = $this->createDatabase();

        $this->assertEquals(
            ['id' => null, 'title' => null, 'isActive' => null],
            $db->post->getDefaults()
        );

        $this->assertEquals('post', $db->post->getName());
        $this->assertEquals('post_id', $db->post->getForeignKey());
        $this->assertSame($db, $db->post->getDatabase());
    }

    public function testArrayAccess()
    {
        $db = $this->createDatabase();

        //Insert
        $db->post[] = ['title' => 'First post', 'isActive' => 1];

        $this->assertTrue(isset($db->post[1]));
        $this->assertSame(1, $db->post->count());

        //Select
        $post = $db->post[1];

        $this->assertInstanceOf(Row::class, $post);

        //Update
        $db->post[1] = ['title' => 'First post edited'];

        $this->assertEquals('First post edited', $post->title);

        //Delete
        unset($db->post[1]);

        $this->assertFalse(isset($db->post[1]));
        $this->assertCount(0, $db->post);
    }
}
