<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Date extends Datetime
{
    protected $format = 'Y-m-d';

    public static function getFactory(): FieldFactory
    {
        return new FieldFactory(
            self::class,
            ['date']
        );
    }
}
