<?php

namespace Riverskies\LaravelDataCollection\Tests\Unit;

use Illuminate\Pagination\LengthAwarePaginator;
use Riverskies\LaravelDataCollection\DataCollection;
use Riverskies\LaravelDataCollection\Tests\TestCase;

class DataCollectionTest extends TestCase
{
    /** @test */
    public function it_is_an_abstract_base_class()
    {
        $reflection = new \ReflectionClass(DataCollection::class);
        $this->assertTrue($reflection->isAbstract());
    }

    /** @test */
    public function it_has_a_final_constructor()
    {
        $reflection = new \ReflectionClass(DataCollection::class);
        $this->assertTrue($reflection->getConstructor()->isFinal());
    }

    /** @test */
    public function it_is_countable()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }
        };

        $this->assertCount(3, $class::all());
    }

    /** @test */
    public function it_has_array_access()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }
        };

        $this->assertEquals(2, $class::all()[1]);
    }

    /** @test */
    public function it_is_arrayable()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }
        };

        $this->assertEquals(2, $class::all()[1]);
    }

    /** @test */
    public function it_is_an_iterator_aggregate()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }
        };

        $this->assertInstanceOf(\ArrayIterator::class, $class::all()->getIterator());
    }

    /** @test */
    public function it_is_jsonable()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }
        };

        $this->assertEquals(json_encode([1, 2, 3]), $class::all()->toJson());
    }

    /** @test */
    public function it_is_json_serializable()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }
        };

        $this->assertEquals(json_encode([1, 2, 3]), json_encode($class));
    }

    /** @test */
    public function it_returns_all_items_when_calling_all()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }
        };

        tap($class::all(), function($collection) use ($class) {
            $this->assertInstanceOf(get_class($class), $collection);
            $this->assertEquals([1, 2, 3], $collection->all());
        });
    }

    /** @test */
    public function it_optionally_has_a_data_mapper_function()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }

            protected function dataMapper($items)
            {
                return $items->map(function($item) {
                    return 2 * $item;
                });
            }
        };

        tap($class::all(), function($collection) use ($class) {
            $this->assertInstanceOf(get_class($class), $collection);
            $this->assertEquals([2, 4, 6], $collection->all());
        });
    }

    /** @test */
    public function it_can_optionally_apply_a_single_filter_without_parameters()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3, 4, 5];
            }

            protected function filteredByEven($item)
            {
                return $item % 2 == 0;
            }
        };

        $collection = $class::filteredBy('even');

        $this->assertEquals([2, 4], $collection->toArray());
    }

    /** @test */
    public function it_can_optionally_apply_a_single_filter_with_parameters()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3];
            }

            protected function filteredByEquals($item, $value)
            {
                return $item == $value;
            }
        };

        $collection = $class::filteredBy(['equals' => 2]);

        $this->assertEquals([2], $collection->toArray());
    }

    /** @test */
    public function it_can_optionally_apply_multiple_filters_without_parameters()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3, 10];
            }

            protected function filteredByEven($item)
            {
                return $item % 2 == 0;
            }

            protected function filteredByUnderTen($item)
            {
                return $item < 10;
            }
        };

        $collectionA = $class::filteredBy('even', 'underTen');
        $collectionB = $class::filteredBy(['even', 'underTen']);

        $this->assertEquals([2], $collectionA->toArray());
        $this->assertEquals([2], $collectionB->toArray());
    }

    /** @test */
    public function it_can_optionally_apply_multiple_filters_with_parameters()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3, 4];
            }

            protected function filteredByGreaterThan($item, $value)
            {
                return $item > $value;
            }

            protected function filteredByDividable($item, $divider)
            {
                return $item % $divider == 0;
            }
        };

        $collection = $class::filteredBy([
            'greaterThan' => 2,
            'dividable' => 4,
        ]);

        $this->assertEquals([4], $collection->toArray());
    }

    /** @test */
    public function it_runs_the_mapper_on_the_filtered_result_set()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3, 4];
            }

            protected function dataMapper($items)
            {
                return $items->map(function($item) {
                    return 10 * $item;
                });
            }

            protected function filteredByEven($item)
            {
                return $item % 2 == 0;
            }
        };

        $collection = $class::filteredBy('even');

        $this->assertEquals([20, 40], $collection->toArray());
    }

    /** @test */
    public function filters_can_be_chained_one_after_the_other()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3, 4];
            }

            protected function filteredByGreaterThan($item, $value)
            {
                return $item > $value;
            }

            protected function filteredByDividable($item, $divider)
            {
                return $item % $divider == 0;
            }
        };

        $collection = $class::filteredBy(['greaterThan' => 2])->filteredBy(['dividable' => 4]);

        $this->assertEquals([4], $collection->toArray());
    }

    /** @test */
    public function it_optionally_applies_ordering_by_an_internal_field_in_ascending_order_by_default()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [
                    (object) ['name' => 'Alpha'],
                    (object) ['name' => 'Delta'],
                    (object) ['name' => 'Beta'],
                ];
            }
        };

        $collection = $class::orderedBy('name');

        $this->assertEquals('Alpha', $collection->get(0)->name);
        $this->assertEquals('Beta', $collection->get(1)->name);
        $this->assertEquals('Delta', $collection->get(2)->name);
    }

    /** @test */
    public function ordering_can_be_descending_by_passing_the_descending_or_desc_keyword_case_insensitively()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [
                    (object) ['name' => 'Alpha'],
                    (object) ['name' => 'Delta'],
                    (object) ['name' => 'Beta'],
                ];
            }
        };

        $collectionA = $class::orderedBy('name', 'desc');
        $collectionB = $class::orderedBy('name', 'descending');
        $collectionC = $class::orderedBy('name', 'DESC');
        $collectionD = $class::orderedBy('name', 'DESCENDING');

        foreach ([$collectionA, $collectionB, $collectionC, $collectionD] as $collection) {
            $this->assertEquals('Delta', $collection->get(0)->name);
            $this->assertEquals('Beta', $collection->get(1)->name);
            $this->assertEquals('Alpha', $collection->get(2)->name);
        }
    }

    /** @test */
    public function ordering_can_be_chained_to_a_filter()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [
                    (object) ['name' => 'Alpha'],
                    (object) ['name' => 'Delta'],
                    (object) ['name' => 'Beta'],
                ];
            }

            protected function filteredByNotAlpha($item)
            {
                return $item->name != 'Alpha';
            }
        };

        $collection = $class::filteredBy('notAlpha')->orderedBy('name', 'asc');

        $this->assertEquals('Beta', $collection->get(0)->name);
        $this->assertEquals('Delta', $collection->get(1)->name);
    }

    /** @test */
    public function it_can_use_pagination_with_default_length()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return ['A', 'B', 'C'];
            }
        };

        $collection = $class::paginate();

        $this->assertInstanceOf(LengthAwarePaginator::class, $collection);
        $this->assertEquals(3, $collection->total());
        $this->assertEquals(10, $collection->perPage());
        $this->assertEquals(1, $collection->lastPage());
        $this->assertFalse($collection->hasMorePages());
    }

    /** @test */
    public function it_can_set_the_per_page_length_on_the_paginator()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return ['A', 'B', 'C'];
            }
        };

        $collection = $class::paginate(2);

        $this->assertInstanceOf(LengthAwarePaginator::class, $collection);
        $this->assertEquals(3, $collection->total());
        $this->assertEquals(2, $collection->perPage());
        $this->assertEquals(2, $collection->lastPage());
        $this->assertTrue($collection->hasMorePages());
    }

    /** @test */
    public function it_can_use_the_pagination_chained()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3, 4];
            }

            protected function filteredByEven($item)
            {
                return $item % 2 == 0;
            }
        };

        $collection = $class::filteredBy('even')->paginate();

        $this->assertInstanceOf(LengthAwarePaginator::class, $collection);
        $this->assertEquals(2, $collection->total());
        $this->assertEquals(10, $collection->perPage());
        $this->assertEquals(1, $collection->lastPage());
        $this->assertFalse($collection->hasMorePages());
    }

    /** @test */
    public function it_does_not_map_its_collection_over_and_over_again()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [1, 2, 3, 4];
            }

            protected function dataMapper($items)
            {
                return $items->map(function($item) {
                    return $item * 10;
                });
            }
        };

        $collection = $class::all();

        $this->assertEquals(10, $collection->first());
        $this->assertEquals(40, $collection->last());
    }
}
