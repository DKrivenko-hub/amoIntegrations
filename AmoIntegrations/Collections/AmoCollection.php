<?php

namespace AmoIntegrations\Collections;

use AmoIntegrations\Interfaces\IEntity;

class AmoCollection implements \Iterator
{

    private int $position;

    private array $collection;

    private int $page;
    private string $curr_link;
    private string $next_link;


    public function __construct(string $objType,  $data)
    {
        $this->position = 0;

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $this->curr_link = $data['_links']['self']['href'];
        $this->next_link = $data['_links']['next']['href'];
        $this->page = $data['_page'];

        $class = "AmoIntegrations\\Collections\\$objType";

        if (!empty($objType) && class_exists($class)) {
            foreach ($data['_embedded'][strtolower($objType)] as $item) {
                $entity = new $class($item);
                if ($entity instanceof IEntity) {
                    $this->collection[] = $entity;
                }
            }
        } else {
            throw new \UnexpectedValueException();
        }
    }


    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->collection[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->collection[$this->position]);
    }

    public function find($key, $value)
    {
        foreach ($this->collection as $item) {
            if ($item->find($key, $value)) {
                return $item;
            }
        }
        return false;
    }
}
