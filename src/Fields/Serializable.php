<?php

namespace SimpleCrud\Fields;

/**
 * To serialize values before save.
 */
class Serializable extends Field
{
    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        return unserialize($data);
    }
}
