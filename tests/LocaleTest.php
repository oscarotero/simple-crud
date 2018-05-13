<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Database;

class LocaleTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title_gl`    TEXT,
    `title_es`    TEXT
);
EOT
        ]);
    }

    public function testMultilanguage()
    {
        $db = $this->createDatabase();
        $db->setAttribute(Database::ATTR_LOCALE, 'gl');

        $post = $db->post->create();

        $post->title = 'Galego';

        $this->assertSame($post->title, $post->title_gl);

        $db->setAttribute(Database::ATTR_LOCALE, 'es');

        $this->assertNotSame($post->title, $post->title_gl);
        $this->assertSame($post->title, $post->title_es);

        $post->title_es = 'Español';

        $this->assertNotSame($post->title_gl, $post->title_es);
        $this->assertTrue(isset($post->title_es));
        $this->assertTrue(isset($post->title));
        $this->assertFalse(isset($post->title_en));
    }
}
