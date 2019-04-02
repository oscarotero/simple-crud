<?php
declare(strict_types = 1);

namespace SimpleCrud\Events;

final class BeforeCreateRow
{
    private $data;

    public function __construct(array $data)
    {
        $this->setData($data);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }
}
