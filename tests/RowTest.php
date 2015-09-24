<?php
use SimpleCrud\SimpleCrud;
use SimpleCrud\EntityFactory;
use SimpleCrud\Entity;

class RowTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $entityFactory = new EntityFactory();
        $entityFactory->setAutocreate('DefaultEntity');

        $this->db = new SimpleCrud(initSqlitePdo(), $entityFactory);
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

    public function testCustomFunction()
    {
        $post = $this->db->post->create([
            'title' => 'THIS IS THE TITLE',
        ])->save();

        $this->assertSame('this is the title', $post->getTitleLowerCase());
        $this->assertSame('this is the title', $post->titleLowerCase);

        $this->db->post->insert()->data(['title' => 'second'])->run();
        $this->assertSame(3, $this->db->post->select()->all()->sumIds());
    }
}

class DefaultEntity extends Entity
{
    protected function init()
    {
        $this->row
            ->registerMethod('getTitleLowerCase', function ($row) {
                return strtolower($row->title);
            })
            ->registerProperty('titleLowerCase', function ($row) {
                return $row->getTitleLowerCase();
            });

        $this->collection->registerMethod('sumIds', function ($collection) {
            return array_sum($collection->id);
        });
    }
}
