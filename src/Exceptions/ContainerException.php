<?php
namespace SimpleCrud;

use Interop\Container\Exception\ContainerException as ContainerExceptionInterface;

class NotFoundException extends SimpleCrudException implements ContainerExceptionInterface
{
}
