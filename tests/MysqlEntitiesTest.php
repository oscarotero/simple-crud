<?php
use SimpleCrud\Adapters\Mysql;
use SimpleCrud\Adapters\AdapterInterface;
use SimpleCrud\EntityFactory;

class MysqlEntitiesTest extends PHPUnit_Framework_TestCase
{
    public function testConnection()
    {
        $db = new Mysql(initMysqlPdo(), new EntityFactory([
            'autocreate' => true,
            'namespace' => 'Custom\\'
        ]));

        $this->assertInstanceOf('SimpleCrud\\Adapters\\AdapterInterface', $db);

        return $db;
    }

    /**
     * @depends testConnection
     */
    public function testAutocreate(AdapterInterface $db)
    {
        $this->assertInstanceOf('Custom\\Posts', $db->posts);
        $this->assertInstanceOf('Custom\\Categories', $db->categories);
        $this->assertInstanceOf('Custom\\Tags', $db->tags);
        $this->assertInstanceOf('Custom\\Tags_in_posts', $db->tags_in_posts);

        return $db;
    }

    /**
     * @depends testAutocreate
     */
    public function testDataToDatabase(AdapterInterface $db)
    {
        $category = $db->categories->create(['name' => 'NEW CATEGORY'])->save();

        $this->assertSame('new category', $category->name);

        $category->reload();

        $this->assertSame('new category', $category->name);
    }

    /**
     * @depends testAutocreate
     */
    public function testDatetimeFields(AdapterInterface $db)
    {
        $this->assertInstanceOf('SimpleCrud\\Fields\\Datetime', $db->posts->fields['pubdate']);

        //Test with Datetime object
        $datetime = new Datetime('now');
        $post = $db->posts->create(['pubdate' => $datetime])->save();

        $post->reload();
        $this->assertSame($datetime->format('Y-m-d H:i:s'), $post->pubdate);

        //Test with a string
        $datetime = date(DATE_RFC2822);
        $post->set(['pubdate' => $datetime])->save();

        $post->reload();
        $this->assertSame(date('Y-m-d H:i:s', strtotime($datetime)), $post->pubdate);
    }

    /**
     * @depends testAutocreate
     */
    public function testDateFields(AdapterInterface $db)
    {
        $this->assertInstanceOf('SimpleCrud\\Fields\\Date', $db->posts->fields['day']);

        //Test with Datetime object
        $date = new Datetime('now');
        $post = $db->posts->create(['day' => $date])->save();

        $post->reload();
        $this->assertSame($date->format('Y-m-d'), $post->day);

        //Test with a string
        $date = date(DATE_RFC2822);
        $post->set(['day' => $date])->save();

        $post->reload();
        $this->assertSame(date('Y-m-d', strtotime($date)), $post->day);
    }

    /**
     * @depends testAutocreate
     */
    public function testSetFields(AdapterInterface $db)
    {
        $this->assertInstanceOf('SimpleCrud\\Fields\\Set', $db->posts->fields['type']);

        //Test with a string
        $post = $db->posts->create(['type' => 'text'])->save();

        $post->reload();
        $this->assertSame(['text'], $post->type);

        //Test with a string
        $post->set(['type' => ['text', 'video']])->save();

        $post->reload();
        $this->assertSame(['text', 'video'], $post->type);
    }

    /**
     * @depends testAutocreate
     */
    public function testCustomField(AdapterInterface $db)
    {
        $this->assertInstanceOf('Custom\\Fields\\Json', $db->tags->fields['name']);

        $tag = $db->tags->create([
            'name' => ['red', 'blue', 'green'],
        ])->save();

        $tag->reload();
        $this->assertSame(['red', 'blue', 'green'], $tag->name);
    }
}
