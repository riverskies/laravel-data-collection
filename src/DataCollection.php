<?php

namespace Riverskies\LaravelDataCollection;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Pagination\LengthAwarePaginator;
use IteratorAggregate;
use JsonSerializable;

abstract class DataCollection implements ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    use CollectionInterfaces;

    /** @var \Illuminate\Support\Collection */
    private $items;

    /** @var bool */
    private $alreadyMapped = false;

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

        if (!$this->alreadyMapped) {
            $this->items = $this->dataMapper($this->items);
            $this->alreadyMapped = true;
        }

        return ($method == 'paginate')
            ? $this->createPaginator(...$arguments)
            : $this->items->{$method}(...$arguments);
    }

    /**
     * @param $method
     * @param $arguments
     * @return DataCollection
     */
    public static function __callStatic($method, $arguments)
    {
        $instance = new static;

        if ($method == 'paginate') {
            return $instance->createPaginator(...$arguments);
        }

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
     * @param $perPage = 10
     * @return LengthAwarePaginator
     */
    private function createPaginator($perPage = 10)
    {
        return new LengthAwarePaginator($this->items, $this->items->count(), $perPage);
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
