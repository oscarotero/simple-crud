<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

class RelationsTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(initSqlitePdo());
    }

    public function _testRelations()
    {
        $this->assertTrue($this->db->post->hasOne('category'));
        $this->assertTrue($this->db->category->hasMany('post'));
        $this->assertTrue($this->db->post_tag->hasOne('tag'));
        $this->assertTrue($this->db->tag->hasMany('post_tag'));
        $this->assertTrue($this->db->post_tag->hasOne('post'));
        $this->assertTrue($this->db->post->hasMany('post_tag'));
        $this->assertTrue($this->db->tag->hasBridge('post'));
        $this->assertTrue($this->db->post->hasBridge('tag'));

        $this->assertSame(Table::RELATION_HAS_ONE, $this->db->post->getRelation('category'));
        $this->assertSame(Table::RELATION_HAS_MANY, $this->db->category->getRelation('post'));
        $this->assertSame(Table::RELATION_HAS_ONE, $this->db->post_tag->getRelation('tag'));
        $this->assertSame(Table::RELATION_HAS_MANY, $this->db->tag->getRelation('post_tag'));
        $this->assertSame(Table::RELATION_HAS_ONE, $this->db->post_tag->getRelation('post'));
        $this->assertSame(Table::RELATION_HAS_MANY, $this->db->post->getRelation('post_tag'));
        $this->assertSame(Table::RELATION_HAS_BRIDGE, $this->db->post->getRelation('tag'));
        $this->assertSame(Table::RELATION_HAS_BRIDGE, $this->db->tag->getRelation('post'));

        $this->assertFalse($this->db->category->hasOne('post'));
        $this->assertFalse($this->db->post->hasMany('category'));
        $this->assertFalse($this->db->tag->hasOne('post_tag'));
        $this->assertFalse($this->db->post_tag->hasMany('tag'));
        $this->assertFalse($this->db->post->hasOne('post_tag'));
        $this->assertFalse($this->db->post_tag->hasMany('post'));
        $this->assertFalse($this->db->post_tag->hasBridge('post'));
        $this->assertFalse($this->db->post_tag->hasBridge('tag'));

        $this->assertNull($this->db->category->getRelation('tag'));
    }

    public function _testCreateRelation()
    {
        $category = $this->db->category->create(['name' => 'Foo'])->save();

        $post = $this->db->post
            ->create(['title' => 'Bar'])
            ->relateWith($category)
            ->save();

        $this->assertSame($post->category_id, $category->id);
        $this->assertSame($category->name, $post->category()->run()->name);

        $post = $category->post()->run();

        $this->assertCount(1, $post);
        $this->assertEquals(1, $post[1]->id);

        $this->assertSame($category->id, $post->category->id);
    }

    public function testCreateManyToManyRelation()
    {
        $post = $this->db->post->create(['title' => 'Title'])->save();
        $tag = $this->db->tag->create(['name' => 'Name'])->save();

        $this->db->post_tag->create()
            ->relateWith($post)
            ->relateWith($tag)
            ->save();

        $relatedTag = $post->tag()->run();

        $this->assertCount(1, $relatedTag);
        $this->assertEquals($tag->id, $relatedTag[1]->id);

        $relation = $post->post_tag[1];

        $this->assertSame($post->id, $relation->post_id);
        $this->assertSame($tag->id, $relation->tag_id);
    }
}
