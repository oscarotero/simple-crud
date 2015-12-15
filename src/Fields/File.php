<?php

namespace SimpleCrud\Fields;

use RuntimeException;

/**
 * To save files
 */
class File extends Field
{
    protected $config = [
        'uploader' => null
    ];

    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        if (!is_string($data)) {
            return $this->upload($data);
        }

        return $data;
    }

    /**
     * Upload the file and return the value
     */
    private function upload($file)
    {
        $uploader = $this->config['uploader'];

        if (empty($uploader) || !is_callable($uploader)) {
            throw new RuntimeException("Not valid uploader");
        }

        return $uploader($file);
    }
}
