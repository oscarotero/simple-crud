<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Json extends Field
{
    protected $config = [
        'assoc' => true,
    ];

    public static function getFactory(): FieldFactory
    {
        return new FieldFactory(
            self::class,
            ['json']
        );
    }

    public function format($value)
    {
        return empty($value) ? [] : json_decode($value, $this->config['assoc']);
    }

    protected function formatToDatabase($value): ?string
    {
        if ($value === null) {
            return $value;
        }

        if (!is_string($value)) {
            return json_encode($value) ?: null;
        }

        return $value ?: null;
    }
}
