<?php

namespace SimpleCrud\Fields;

use SimpleCrud\SimpleCrud;

/**
 * To slugify values before save.
 */
class Point extends Field
{
    /**
     * {@inheritdoc}
     */
    public function getSelectExpression($as = null)
    {
        $tableName = $this->table->getName();
        $fieldName = $this->name;

        if ($as) {
            return "asText(`{$tableName}`.`{$fieldName}`) as `{$as}`";
        }

        return "asText(`{$tableName}`.`{$fieldName}`) as `{$fieldName}`";
    }

    /**
     * {@inheritdoc}
     */
    public function getValueExpression($mark)
    {
        return "PointFromText($mark)";
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        //POINT(X Y)
        if ($data !== null) {
            $points = explode(' ', substr($data, 6, -1), 2);

            return [
                floatval($points[0]),
                floatval($points[1]),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        if (self::isValid($data)) {
            return 'POINT('.implode(' ', $data).')';
        }
    }

    /**
     * Check whether the value is valid before save in the database
     *
     * @param mixed $data
     *
     * @return bool
     */
    private static function isValid($data)
    {
        if (!is_array($data)) {
            return false;
        }

        if (!isset($data[0]) || !isset($data[1]) || count($data) > 2) {
            return false;
        }

        return is_numeric($data[0]) && is_numeric($data[1]);
    }
}
