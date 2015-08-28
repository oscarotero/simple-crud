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

    public function _testRelations()
    {
        $this->assertTrue($this->db->posts->hasOne('categories'));
        $this->assertTrue($this->db->categories->hasMany('posts'));
        $this->assertTrue($this->db->posts__tags->hasOne('tags'));
        $this->assertTrue($this->db->tags->hasMany('posts__tags'));
        $this->assertTrue($this->db->posts__tags->hasOne('posts'));
        $this->assertTrue($this->db->posts->hasMany('posts__tags'));
        $this->assertTrue($this->db->tags->hasBridge('posts'));
        $this->assertTrue($this->db->posts->hasBridge('tags'));

        $this->assertSame(Entity::RELATION_HAS_ONE, $this->db->posts->getRelation('categories'));
        $this->assertSame(Entity::RELATION_HAS_MANY, $this->db->categories->getRelation('posts'));
        $this->assertSame(Entity::RELATION_HAS_ONE, $this->db->posts__tags->getRelation('tags'));
        $this->assertSame(Entity::RELATION_HAS_MANY, $this->db->tags->getRelation('posts__tags'));
        $this->assertSame(Entity::RELATION_HAS_ONE, $this->db->posts__tags->getRelation('posts'));
        $this->assertSame(Entity::RELATION_HAS_MANY, $this->db->posts->getRelation('posts__tags'));
        $this->assertSame(Entity::RELATION_HAS_BRIDGE, $this->db->posts->getRelation('tags'));
        $this->assertSame(Entity::RELATION_HAS_BRIDGE, $this->db->tags->getRelation('posts'));

        $this->assertFalse($this->db->categories->hasOne('posts'));
        $this->assertFalse($this->db->posts->hasMany('categories'));
        $this->assertFalse($this->db->tags->hasOne('posts__tags'));
        $this->assertFalse($this->db->posts__tags->hasMany('tags'));
        $this->assertFalse($this->db->posts->hasOne('posts__tags'));
        $this->assertFalse($this->db->posts__tags->hasMany('posts'));
        $this->assertFalse($this->db->posts__tags->hasBridge('posts'));
        $this->assertFalse($this->db->posts__tags->hasBridge('tags'));

        $this->assertNull($this->db->categories->getRelation('tags'));
    }

    public function _testCreateRelation()
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

        $this->db->posts__tags->create()
            ->relateWith($post)
            ->relateWith($tag)
            ->save();

        $relatedTags = $post->select('tags')->all();

        $this->assertCount(1, $relatedTags);
        $this->assertEquals($tag->id, $relatedTags[1]->id);

        $relation = $post->posts__tags[1];

        $this->assertSame($post->id, $relation->posts_id);
        $this->assertSame($tag->id, $relation->tags_id);
    }
}
