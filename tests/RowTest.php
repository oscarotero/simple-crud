<?php
use SimpleCrud\SimpleCrud;
use SimpleCrud\Entity;

class RowTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(initSqlitePdo());
    }

    public function testPost()
    {
        $post = $this->db->post->create();

        $this->assertInstanceOf('SimpleCrud\\Row', $post);

        $this->assertNull($post->id);
        $this->assertNull($post->title);
        $this->assertNull($post->categories_id);
        $this->assertNull($post->pubdate);
        $this->assertNull($post->type);

        $this->assertNull($post->get('id'));
        $this->assertNull($post->get('title'));
        $this->assertNull($post->get('categories_id'));
        $this->assertNull($post->get('pubdate'));
        $this->assertNull($post->get('type'));

        $post->title = 'Hello world';

        $this->assertSame('Hello world', $post->title);
        $this->assertSame('Hello world', $post->get('title'));

        $post->save();

        $this->assertEquals(1, $post->id);
        $this->assertEquals(1, $post->get('id'));
    }
}
