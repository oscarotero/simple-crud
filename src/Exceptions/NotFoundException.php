<?php
namespace SimpleCrud\Exceptions;

use Interop\Container\Exception\NotFoundException as NotFoundExceptionInterface;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
