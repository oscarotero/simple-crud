<?php

namespace SimpleCrud\Fields;

use Latitude\QueryBuilder\StatementInterface;
use function Latitude\QueryBuilder\fn;
use function Latitude\QueryBuilder\param;

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
    public function rowValue($value, array $data = [])
    {
        //POINT(X Y)
        if ($value !== null) {
            $points = explode(' ', substr($value, 6, -1), 2);

            return [
                floatval($points[0]),
                floatval($points[1]),
            ];
        }
    }

    public function param($value): StatementInterface
    {
        if ($value instanceof StatementInterface) {
            return $value;
        }

        if (self::isValid($value)) {
            return fn('POINT', param($value[0]), param($value[1]));
        }

        return param(null);
    }

    /**
     * Check whether the value is valid before save in the database
     * @param mixed $data
     */
    private static function isValid($data): bool
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
