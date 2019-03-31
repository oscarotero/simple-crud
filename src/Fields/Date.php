<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Date extends Datetime
{
    protected $format = 'Y-m-d';
}
