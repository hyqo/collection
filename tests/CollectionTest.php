<?php

namespace Hyqo\Collection\Test;

use Hyqo\Collection\Test\Fixtures\Item;
use Hyqo\Collection\Test\Fixtures\ItemCollection;

use JsonException;
use PHPUnit\Framework\TestCase;

use function Hyqo\Collection\collect;

class CollectionTest extends TestCase
{
    /**
     * @return array{"1.1": Item, "1.2": Item, "2.1": Item, "2.2": Item}
     */
    private function mockProducts(): array
    {
        return [
            '1.1' => new Item('product 1.1', 1),
            '1.2' => new Item('product 1.2', 2),
            '2.1' => new Item('product 2.1', 3),
            '2.2' => new Item('product 2.2', 4),
        ];
    }

    private function mockCollection(): ItemCollection
    {
        $collection = new ItemCollection();

        $products = $this->mockProducts();

        foreach ($products as $product) {
            $collection->add($product);
        }

        return $collection;
    }

    private function mockLazyCollection(): ItemCollection
    {
        $collection = new ItemCollection();

        $products = $this->mockProducts();

        $collection->setLoader(function () use ($products) {
            return $products;
        });

        return $collection;
    }

    public function test_lazy_add(): void
    {
        $products = $this->mockProducts();
        $collection = $this->mockLazyCollection();

        $newProduct = new Item('product 3.1', 31);

        $collection->add($newProduct);

        $this->assertEquals([...array_values($products), $newProduct], $collection->toArray());
    }

    public function test_get(): void
    {
        $products = $this->mockProducts();
        $collection = $this->mockCollection();

        $collection->get(10);

        $this->assertNull($collection->get(10));
        $this->assertEquals($products['1.1'], $collection->get(0));
    }

    public function test_lazy_get(): void
    {
        $products = $this->mockProducts();
        $collection = $this->mockLazyCollection();

        $collection->get(10);

        $this->assertNull($collection->get(10));
        $this->assertEquals($products['1.1'], $collection->get(0));
    }

    /**
     * @throws JsonException
     */
    public function test_slice(): void
    {
        $products = $this->mockProducts();
        $collection = $this->mockCollection();

        $slice = $collection->slice(1, 1)->slice(0)->add($products['2.2']);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode(new ItemCollection([$products['1.2'], $products['2.2']]), JSON_THROW_ON_ERROR),
            (string)json_encode($slice, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @throws JsonException
     */
    public function test_lazy_slice(): void
    {
        $products = $this->mockProducts();
        $collection = $this->mockLazyCollection();

        $slice = $collection->slice(1, 1)->slice(0)->add($products['2.2']);

        $this->assertJsonStringEqualsJsonString(
            (string)json_encode(new ItemCollection([$products['1.2'], $products['2.2']]), JSON_THROW_ON_ERROR),
            (string)json_encode($slice, JSON_THROW_ON_ERROR)
        );
    }

    public function test_copy(): void
    {
        $collection = $this->mockCollection();
        $collectionCopy = $collection->copy();

        $this->assertEquals($collection->toArray(), $collectionCopy->toArray());
        $this->assertNotSame($collection, $collectionCopy);
    }

    public function test_foreach(): void
    {
        $products = $this->mockProducts();
        $collection = $this->mockCollection();

        foreach ($collection->copy() as $i => $item) {
            $this->assertEquals($products[array_keys($products)[$i]], $item);
        }
    }

    public function test_count(): void
    {
        $collection = $this->mockCollection();

        $this->assertCount(4, $collection->slice(0));

        $this->assertCount(3, collect([1, 2, 3]));
    }

    public function test_filter(): void
    {
        $products = $this->mockProducts();
        $collection = $this->mockCollection();

        $product1Collection = $collection->slice(0)->filter(
            function (Item $item): bool {
                return str_starts_with($item->title, 'product 1.');
            }
        );
        $product2Collection = $collection->slice(0)->filter(
            function (Item $item): bool {
                return str_starts_with($item->title, 'product 2.');
            }
        );

        $this->assertEquals(
            iterator_to_array(new ItemCollection([$products['1.1'], $products['1.2']])),
            iterator_to_array($product1Collection)
        );
        $this->assertEquals(
            iterator_to_array(new ItemCollection([$products['2.1'], $products['2.2']])),
            iterator_to_array($product2Collection)
        );
    }

    public function test_reduce(): void
    {
        $collection = $this->mockCollection();

        $product1Collection = $collection->filter(
            function (Item $item) {
                return str_starts_with($item->title, 'product 1.');
            }
        );
        $product2Collection = $collection->filter(
            function (Item $item) {
                return str_starts_with($item->title, 'product 2.');
            }
        );

        $product1Amount = $product1Collection->slice(0)->reduce(
            function ($carry, Item $item) {
                return $carry + $item->amount;
            }
        );
        $product2Amount = $product2Collection->slice(0)->reduce(
            function ($carry, Item $item) {
                return $carry + $item->amount;
            }
        );

        $this->assertEquals(3, $product1Amount);
        $this->assertEquals(7, $product2Amount);
    }

    public function test_each(): void
    {
        $collection = $this->mockCollection();

        $collection
            ->each(function (Item $item) {
                $this->assertInstanceOf(Item::class, $item);
            });
    }

    public function test_map(): void
    {
        $collection = $this->mockCollection();

        $expectedProducts = array_map(static function (Item $item) {
            $item->amount++;
            return $item;
        }, $this->mockProducts());

        $this->assertEquals(
            (new ItemCollection($expectedProducts))->toArray(),
            $collection
                ->map(function (Item $item) {
                    $newItem = clone $item;
                    $newItem->amount++;

                    yield $newItem;
                })
                ->toArray()
        );

        $this->assertEquals(
            (new ItemCollection($expectedProducts))->toArray(),
            $collection
                ->map(function (Item $item) {
                    $newItem = clone $item;
                    $newItem->amount++;

                    return $newItem;
                })
                ->toArray()
        );
    }

    public function test_chunk(): void
    {
        $collection = $this->mockCollection();

        $chunks = $collection->chunk(3);

        $this->assertCount(2, iterator_to_array($chunks));
    }

    /**
     * @throws JsonException
     */
    public function test_chunk_json(): void
    {
        $products = $this->mockProducts();
        $collection = $this->mockCollection();

        $chunks = $collection->chunk(3);

        $this->assertEquals(
            json_encode([
                [
                    $products['1.1'],
                    $products['1.2'],
                    $products['2.1'],
                ],
                [
                    $products['2.2']
                ]
            ], JSON_THROW_ON_ERROR),
            json_encode(iterator_to_array($chunks), JSON_THROW_ON_ERROR)
        );
    }

    public function test_big_chunk(): void
    {
        $collection = new ItemCollection();

        $count = 100000;
        $perChunk = 100;
        $pages = (int)ceil($count / $perChunk);

        for ($i = 1; $i <= $count; $i++) {
            $collection->add(new Item("title", $i));
        }

        $chunks = $collection->chunk($perChunk);

        $this->assertCount($pages, iterator_to_array($chunks));
    }

    public function test_toArray(): void
    {
        $collection = $this->mockCollection();
        $expectedProducts = $this->mockProducts();

        $this->assertEquals(
            $expectedProducts,
            $collection
                ->toArray(function (Item $item) {
                    yield str_replace('product ', '', $item->title) => $item;
                })
        );

        $this->assertEquals(
            array_values($expectedProducts),
            $collection
                ->toArray(function (Item $item) {
                    return $item;
                })
        );

        $this->assertEquals(
            array_values($expectedProducts),
            $collection->toArray()
        );
    }
}
