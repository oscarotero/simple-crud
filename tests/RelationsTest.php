<?php
use SimpleCrud\SimpleCrud;
use SimpleCrud\Entity;

class RelationsTest extends PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new SimpleCrud(initSqlitePdo());
    }

    public function testRelations()
    {
        $this->assertTrue($this->db->posts->hasOne('categories'));
        $this->assertTrue($this->db->categories->hasMany('posts'));
        $this->assertTrue($this->db->tags_in_posts->hasOne('tags'));
        $this->assertTrue($this->db->tags->hasMany('tags_in_posts'));
        $this->assertTrue($this->db->tags_in_posts->hasOne('posts'));
        $this->assertTrue($this->db->posts->hasMany('tags_in_posts'));

        $this->assertSame(Entity::RELATION_HAS_ONE, $this->db->posts->getRelation('categories'));
        $this->assertSame(Entity::RELATION_HAS_MANY, $this->db->categories->getRelation('posts'));
        $this->assertSame(Entity::RELATION_HAS_ONE, $this->db->tags_in_posts->getRelation('tags'));
        $this->assertSame(Entity::RELATION_HAS_MANY, $this->db->tags->getRelation('tags_in_posts'));
        $this->assertSame(Entity::RELATION_HAS_ONE, $this->db->tags_in_posts->getRelation('posts'));
        $this->assertSame(Entity::RELATION_HAS_MANY, $this->db->posts->getRelation('tags_in_posts'));

        $this->assertFalse($this->db->categories->hasOne('posts'));
        $this->assertFalse($this->db->posts->hasMany('categories'));
        $this->assertFalse($this->db->tags->hasOne('tags_in_posts'));
        $this->assertFalse($this->db->tags_in_posts->hasMany('tags'));
        $this->assertFalse($this->db->posts->hasOne('tags_in_posts'));
        $this->assertFalse($this->db->tags_in_posts->hasMany('posts'));

        $this->assertNull($this->db->categories->getRelation('tags'));
    }

    public function testCreateRelation()
    {
        $category = $this->db->categories->create(['name' => 'Foo'])->save();

        $post = $this->db->posts
            ->create(['title' => 'Bar'])
            ->relateWith($category)
            ->save();

        $this->assertSame($post->categories_id, $category->id);
        $this->assertSame($category->name, $post->select('categories')->one()->name);

        $posts = $category->select('posts')->all();

        $this->assertCount(1, $posts);
        $this->assertEquals(1, $posts[1]->id);

        $this->assertSame($category->id, $post->categories->id);
    }

    public function testCreateManyToManyRelation()
    {
        $post = $this->db->posts->create(['title' => 'Title'])->save();
        $tag = $this->db->tags->create(['name' => 'Name'])->save();

        $this->db->tags_in_posts->create()
            ->relateWith($post)
            ->relateWith($tag)
            ->save();

        $relatedTags = $post->select('tags', 'tags_in_posts')->all();

        $this->assertCount(1, $relatedTags);
        $this->assertEquals($tag->id, $relatedTags[1]->id);

        $relation = $post->tags_in_posts[1];

        $this->assertSame($post->id, $relation->posts_id);
        $this->assertSame($tag->id, $relation->tags_id);
    }
}
