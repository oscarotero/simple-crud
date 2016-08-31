<?php

namespace SimpleCrud\Fields;

/**
 * Normalices datetime values.
 */
class Datetime extends Field
{
    protected $format = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        if (empty($data)) {
            return;
        }

        if (is_string($data)) {
            return date($this->format, strtotime($data));
        }

        if ($data instanceof \Datetime) {
            return $data->format($this->format);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        return $data ? new \Datetime($data) : null;
    }
}
