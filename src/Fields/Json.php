<?php

namespace SimpleCrud\Fields;

/**
 * To stringify json values before save.
 */
class Json extends Field
{
    protected $config = [
        'assoc' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        if (!is_string($data)) {
            return json_encode($data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        return empty($data) ? [] : json_decode($data, $this->config['assoc']);
    }
}
