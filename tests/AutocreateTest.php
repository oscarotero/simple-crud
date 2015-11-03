<?php

use SimpleCrud\SimpleCrud;

class AutocreateTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(initSqlitePdo());
    }

    public function testPosts()
    {
        $post = $this->db->post;

        $this->assertInstanceOf('SimpleCrud\\Entity', $post);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $post->getDb());

        $this->assertCount(6, $post->fields);

        $this->assertEquals('post', $post->name);
        $this->assertEquals('post_id', $post->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Integer', $post->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $post->fields['title']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Integer', $post->fields['category_id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Datetime', $post->fields['publishedAt']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Boolean', $post->fields['isActive']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $post->fields['type']);
    }

    public function testCategories()
    {
        $category = $this->db->category;

        $this->assertInstanceOf('SimpleCrud\\Entity', $category);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $category->getDb());

        $this->assertCount(2, $category->fields);

        $this->assertEquals('category', $category->name);
        $this->assertEquals('category_id', $category->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Integer', $category->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $category->fields['name']);
    }

    public function testTags()
    {
        $tag = $this->db->tag;

        $this->assertInstanceOf('SimpleCrud\\Entity', $tag);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $tag->getDb());

        $this->assertCount(2, $tag->fields);

        $this->assertEquals('tag', $tag->name);
        $this->assertEquals('tag_id', $tag->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Integer', $tag->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $tag->fields['name']);
    }

    public function testTagPost()
    {
        $post_tag = $this->db->post_tag;

        $this->assertInstanceOf('SimpleCrud\\Entity', $post_tag);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $post_tag->getDb());

        $this->assertCount(3, $post_tag->fields);

        $this->assertEquals('post_tag', $post_tag->name);
        $this->assertEquals('post_tag_id', $post_tag->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Integer', $post_tag->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Integer', $post_tag->fields['tag_id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Integer', $post_tag->fields['post_id']);
    }

    public function testTagsCounter()
    {
        $tagsCounter = $this->db->tagsCounter;

        $this->assertInstanceOf('SimpleCrud\\Entity', $tagsCounter);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $tagsCounter->getDb());

        $this->assertCount(2, $tagsCounter->fields);

        $this->assertEquals('tagsCounter', $tagsCounter->name);
        $this->assertEquals('tagsCounter_id', $tagsCounter->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Integer', $tagsCounter->fields['tag_id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $tagsCounter->fields['total']);
    }
}
