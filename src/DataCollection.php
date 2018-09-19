<?php

namespace Riverskies\LaravelDataCollection;

abstract class DataCollection
{
    private $items;

    abstract protected function getData();

    public function __construct()
    {
        $this->items = collect($this->getData());
    }

    protected function dataMapper($items)
    {
        return $items;
    }

    public function __call($method, $arguments)
    {
        switch ($method) {
            case 'get':
                if (empty($arguments)) {
                    $method = 'all';
                }
                break;
            case 'filteredBy':
                $this->filterItems($arguments);
                return $this;
                break;
        }

        $this->items = $this->dataMapper($this->items);

        return $this->items->{$method}(...$arguments);
    }

    public static function __callStatic($method, $arguments)
    {
        $instance = new static;

        switch ($method) {
            case 'filteredBy':
                $instance->filterItems($arguments);
                break;
        }

        return $instance;
    }

    private function filterItems($arguments)
    {
        foreach ($arguments as $argument) {
            foreach (collect($argument) as $key => $value) {
                [$method, $params] = self::getFilter([$key => $value]);
                $this->items = $this->items->filter(function($item) use ($method, $params) {
                    return $this->$method($item, $params);
                })->values();
            }
        }
    }

    private static function getFilter($value)
    {
        $value = collect($value);
        $key = $value->keys()->first();
        $value = $value->first();

        $filter = is_numeric($key) ? ucfirst($value) : ucfirst($key);
        return ["filteredBy{$filter}", $value];
    }
}
