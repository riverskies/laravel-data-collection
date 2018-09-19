<?php

namespace Riverskies\LaravelDataCollection;

abstract class DataCollection
{
    /** @var \Illuminate\Support\Collection */
    private $items;

    /**
     * @return mixed
     */
    abstract protected function getData();

    /**
     * DataCollection constructor.
     */
    final public function __construct()
    {
        $this->items = collect($this->getData());
    }

    /**
     * Optional - used to map resulting data freely.
     *
     * @param $items
     * @return mixed
     */
    protected function dataMapper($items)
    {
        return $items;
    }

    /**
     * @param $method
     * @param $arguments
     * @return $this
     */
    public function __call($method, $arguments)
    {
        if ($this->applyCriteria($method, $arguments)) {
            return $this;
        }

        $this->items = $this->dataMapper($this->items);

        return $this->items->{$method}(...$arguments);
    }

    /**
     * @param $method
     * @param $arguments
     * @return DataCollection
     */
    public static function __callStatic($method, $arguments)
    {
        $instance = new static;

        $instance->applyCriteria($method, $arguments);

        return $instance;
    }

    /**
     * @param $method
     * @param $arguments
     * @return bool
     */
    private function applyCriteria($method, $arguments)
    {
        switch ($method) {
            case 'filteredBy':
                return $this->filterItems($arguments);
            case 'orderedBy':
                return $this->orderItems(...$arguments);
        }
    }

    /**
     * @param $arguments
     * @return bool
     */
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

        return true;
    }

    /**
     * @param $field
     * @param string $order
     * @return bool
     */
    private function orderItems($field, $order = 'asc')
    {
        $direction = rtrim(strtolower($order), 'ending');
        $sorting = $direction == 'desc' ? 'sortByDesc' : 'sortBy';

        $this->items = $this->items->$sorting($field)->values();

        return true;
    }

    /**
     * @param $value
     * @return array
     */
    private static function getFilter($value)
    {
        $value = collect($value);
        $key = $value->keys()->first();
        $value = $value->first();

        $filter = is_numeric($key) ? ucfirst($value) : ucfirst($key);
        return ["filteredBy{$filter}", $value];
    }
}
