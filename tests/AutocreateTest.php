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

        $this->assertNull($posts->defaults['id']);
        $this->assertNull($posts->defaults['title']);
        $this->assertNull($posts->defaults['categories_id']);
        $this->assertNull($posts->defaults['pubdate']);
        $this->assertNull($posts->defaults['type']);
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

        $this->assertNull($categories->defaults['id']);
        $this->assertNull($categories->defaults['name']);
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

        $this->assertNull($tags->defaults['id']);
        $this->assertNull($tags->defaults['name']);
    }

    public function testTagsPosts()
    {
        $tags_in_posts = $this->db->tags_in_posts;

        $this->assertInstanceOf('SimpleCrud\\Entity', $tags_in_posts);
        $this->assertInstanceOf('SimpleCrud\\SimpleCrud', $tags_in_posts->getDb());

        $this->assertCount(3, $tags_in_posts->fields);

        $this->assertEquals('tags_in_posts', $tags_in_posts->name);
        $this->assertEquals('tags_in_posts', $tags_in_posts->table);
        $this->assertEquals('tags_in_posts_id', $tags_in_posts->foreignKey);

        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $tags_in_posts->fields['id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $tags_in_posts->fields['tags_id']);
        $this->assertInstanceOf('SimpleCrud\\Fields\\Field', $tags_in_posts->fields['posts_id']);

        $this->assertNull($tags_in_posts->defaults['id']);
        $this->assertNull($tags_in_posts->defaults['tags_id']);
        $this->assertNull($tags_in_posts->defaults['posts_id']);
    }
}
