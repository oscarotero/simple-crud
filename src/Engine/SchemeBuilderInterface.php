<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine;

use SimpleCrud\SimpleCrud;

interface SchemeBuilderInterface
{
    const HAS_ONE = 1;
    const HAS_MANY = 2;
    const HAS_MANY_TO_MANY = 4;

    public static function buildScheme(SimpleCrud $db): array;
}
