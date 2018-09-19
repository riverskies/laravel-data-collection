<?php

namespace Tests\Unit;

use Riverskies\LaravelDataCollection\DataCollection;
use Tests\TestCase;

class DataCollectionTest extends TestCase
{
    /** @test */
    public function it_is_an_abstract_base_class()
    {
        $reflection = new \ReflectionClass(DataCollection::class);
        $this->assertTrue($reflection->isAbstract());
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

        $collection = $class::filteredBy('even')->get();

        $this->assertEquals([2, 4], $collection);
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

        $collection = $class::filteredBy(['equals' => 2])->get();

        $this->assertEquals([2], $collection);
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

        $collectionA = $class::filteredBy('even', 'underTen')->get();
        $collectionB = $class::filteredBy(['even', 'underTen'])->get();

        $this->assertEquals([2], $collectionA);
        $this->assertEquals([2], $collectionB);
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
    public function it_optionally_apply_ordering_by_an_internal_field_in_ascending_order_by_default()
    {
        $class = new class extends DataCollection
        {
            protected function getData()
            {
                return [
                    (object)[ 'name' => 'Alpha' ],
                    (object)[ 'name' => 'Delta' ],
                    (object)[ 'name' => 'Beta' ],
                ];
            }
        };

        $collection = $class::orderedBy('name')->get();

        $this->assertEquals([
            (object)[ 'name' => 'Alpha' ],
            (object)[ 'name' => 'Beta' ],
            (object)[ 'name' => 'Delta' ],
        ], $collection);
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

        $collectionA = $class::orderedBy('name', 'desc')->get();
        $collectionB = $class::orderedBy('name', 'descending')->get();
        $collectionC = $class::orderedBy('name', 'DESC')->get();
        $collectionD = $class::orderedBy('name', 'DESCENDING')->get();

        $reversed = [
            (object) ['name' => 'Delta'],
            (object) ['name' => 'Beta'],
            (object) ['name' => 'Alpha'],
        ];

        $this->assertEquals($reversed, $collectionA);
        $this->assertEquals($reversed, $collectionB);
        $this->assertEquals($reversed, $collectionC);
        $this->assertEquals($reversed, $collectionD);
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

        $collection = $class::filteredBy('notAlpha')->orderedBy('name')->get();

        $this->assertEquals([
            (object) ['name' => 'Beta'],
            (object) ['name' => 'Delta'],
        ], $collection);
    }

}
