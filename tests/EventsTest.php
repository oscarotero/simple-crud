<?php
namespace SimpleCrud\Tests;

use Psr\EventDispatcher\EventDispatcherInterface;
use SimpleCrud\Events\BeforeSaveRow;
use SimpleCrud\Events\CreateSelectQuery;

class EventsTest extends AbstractTestCase
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
            ,
<<<'EOT'
CREATE TABLE "comment" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `text`        TEXT,
    `post_id`     INTEGER
);
EOT
        ]);
    }

    public function testTable()
    {
        $dispatcher = new EventDispatcher\Dispatcher();

        $db = $this->createDatabase();
        $db->post->setEventDispatcher($dispatcher);

        $this->assertInstanceOf(EventDispatcherInterface::class, $db->post->getEventDispatcher());

        $dispatcher->listen(CreateSelectQuery::class, function ($event) {
            $event->getQuery()->where('isActive = 1');
        });

        $query = $db->post->select();
        $this->assertEquals(
            (string) $query,
<<<'SQL'
SELECT
    `post`.`id`,
    `post`.`title`,
    `post`.`isActive`
FROM
    `post`
WHERE
    isActive = 1
SQL
        );

        $db->post[] = ['title' => 'First Post', 'isActive' => 1];
        $db->post[] = ['title' => 'Second Post', 'isActive' => 0];
        $db->comment[] = ['text' => 'First Post comment', 'post_id' => 1];
        $db->comment[] = ['text' => 'Second Post comment', 'post_id' => 2];

        $allPosts = $db->post->select()->run();

        $this->assertCount(1, $allPosts);
        $this->assertSame($db->post[1], $db->comment[1]->post);
        $this->assertNull($db->comment[2]->post);

        $dispatcher->listen(BeforeSaveRow::class, function ($event) {
            $row = $event->getRow();
            $row->isActive = true;
        });

        $post = $db->post[2];
        $post->title = 'Modified title';
        $post->isActive = false;

        $this->assertSame('Modified title', $post->title);
        $this->assertSame(false, $post->isActive);

        $post->save();

        $this->assertSame('Modified title', $post->title);
        $this->assertSame(true, $post->isActive);

        $db->post->clearCache();
        $post = $db->post[2];

        $this->assertSame('Modified title', $post->title);
        $this->assertSame(true, $post->isActive);
    }
}
