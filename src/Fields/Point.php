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
        if (is_array($data)) {
            return 'POINT('.implode(' ', $data).')';
        }
    }
}
