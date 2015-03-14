<?php
namespace Custom {
    class Posts extends \SimpleCrud\Entity
    {
        public $fields = [
            'id',
            'title',
            'categories_id',
            'pubdate' => 'datetime',
            'day' => 'date',
            'time',
            'type' => 'set',
        ];
    }

    class Categories extends \SimpleCrud\Entity
    {
        public $fields = [
            'id',
            'name',
        ];

        public function dataToDatabase(array $data, $new)
        {
            $data['name'] = strtolower($data['name']);

            return $data;
        }
    }

    class Tags extends \SimpleCrud\Entity
    {
        public $fields = [
            'id',
            'name' => 'json',
        ];
    }

    class Tags_in_posts extends \SimpleCrud\Entity
    {
        public $fields = [
            'id',
            'posts_id',
            'tags_id',
        ];
    }
}

namespace Custom\Fields {
    class Json extends \SimpleCrud\Fields\Field
    {
        public function dataToDatabase($data)
        {
            if (is_string($data)) {
                return $data;
            }

            return json_encode($data);
        }

        public function dataFromDatabase($data)
        {
            if ($data) {
                return json_decode($data, true);
            }
        }
    }
}
