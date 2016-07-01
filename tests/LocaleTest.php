<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

class LocaleTest extends PHPUnit_Framework_TestCase
{
    private $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(new PDO('sqlite::memory:'));

        $this->db->executeTransaction(function ($db) {
            $db->execute(
<<<EOT
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title_gl`    TEXT,
    `title_es`    TEXT
);
EOT
            );
        });
    }

    public function testRow()
    {
        $db = $this->db;
        $db->setAttribute(SimpleCrud::ATTR_LOCALE, 'gl');

        $post = $db->post->create();

        $post->title = 'Galego';

        $this->assertSame($post->title, $post->title_gl);

        $db->setAttribute(SimpleCrud::ATTR_LOCALE, 'es');
        $this->assertNotSame($post->title, $post->title_gl);
        $this->assertSame($post->title, $post->title_es);

        $post->title_es = 'Español';
        $this->assertNotSame($post->title_gl, $post->title_es);

        $this->assertTrue(isset($post->title_es));
        $this->assertTrue(isset($post->title));
        $this->assertFalse(isset($post->title_en));
    }
}
