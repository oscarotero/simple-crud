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
        $posts = $this->db->posts;

        $this->assertInstanceOf('SimpleCrud\\Entity', $posts);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $posts->getDb());

        $this->assertCount(5, $posts->fields);

        $this->assertEquals('posts', $posts->name);
        $this->assertEquals('posts', $posts->table);
        $this->assertEquals('posts_id', $posts->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $posts->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $posts->fields['title']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $posts->fields['categories_id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $posts->fields['pubdate']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $posts->fields['type']);
    }

    public function testCategories()
    {
        $categories = $this->db->categories;

        $this->assertInstanceOf('SimpleCrud\\Entity', $categories);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $categories->getDb());

        $this->assertCount(2, $categories->fields);

        $this->assertEquals('categories', $categories->name);
        $this->assertEquals('categories', $categories->table);
        $this->assertEquals('categories_id', $categories->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $categories->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $categories->fields['name']);
    }

    public function testTags()
    {
        $tags = $this->db->tags;

        $this->assertInstanceOf('SimpleCrud\\Entity', $tags);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $tags->getDb());

        $this->assertCount(2, $tags->fields);

        $this->assertEquals('tags', $tags->name);
        $this->assertEquals('tags', $tags->table);
        $this->assertEquals('tags_id', $tags->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $tags->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $tags->fields['name']);
    }

    public function testTagsPosts()
    {
        $posts__tags = $this->db->posts__tags;

        $this->assertInstanceOf('SimpleCrud\\Entity', $posts__tags);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $posts__tags->getDb());

        $this->assertCount(3, $posts__tags->fields);

        $this->assertEquals('posts__tags', $posts__tags->name);
        $this->assertEquals('posts__tags', $posts__tags->table);
        $this->assertEquals('posts__tags_id', $posts__tags->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $posts__tags->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $posts__tags->fields['tags_id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $posts__tags->fields['posts_id']);
    }
}
