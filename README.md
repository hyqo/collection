# collection

![Packagist Version](https://img.shields.io/packagist/v/hyqo/collection?style=flat-square)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/hyqo/collection?style=flat-square)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/hyqo/collection/tests.yml?branch=main&style=flat-square&label=tests)

Basic collection with [Generics support](https://blog.jetbrains.com/phpstorm/tag/generics/)

<img alt="example" src="https://raw.githubusercontent.com/hyqo/assets/master/collection/example.png" width="800">

## Install

```sh
composer require hyqo/collection
```

## Usage

For example, we have a `Product` class that we want to wrap in a collection:

```php
class Product 
{
    public $title;
    public $amount;
    
    public function __construct(string $title, int $amount){
        $this->title = $title;
        $this->amount = $amount;
    }
}
```

Create a collection:

```php
use \Hyqo\Collection\Collection;
use function \Hyqo\Collection\collect;

$collection = new Collection([new Product('foo', 10), new Product('bar', 2)]);
$collection = collect([new Product('foo', 10), new Product('bar', 2)]);
```

### Auto-completion

There are three ways for code auto-completion:

**1.** Create a collection with items (not empty):

```php
use Hyqo\Collection\Collection;

$collection = new Collection([new Product('foo', 10), new Product('bar', 2)]);
```

**2.** Use PHPDoc with Generics annotation:

```php
use Hyqo\Collection\Collection;

/** @var Collection<Product> $collection */
$collection = new Collection();
```

**3.** Use your own class with `@extends` annotation:

```php
use Hyqo\Collection\Collection;

/** @extends Collection<Product> */
class ProductCollection extends Collection 
{
}


$collection = new ProductCollection();
```

Now you have auto-completion (see the picture above)

## Methods

### add

```php
function add($item): static
```

Add new item to a collection:

```php
$collection->add($item);
```

### get

```php
function get(int $index): T|null
```

Get item of a collection by index:

```php
$collection->get(0);
```

### each

```php
function each(callable $closure): static<T>
```

Pass each item to a closure:

```php
$collection->each(function(Product $product) {
    // do something
});
```

### map

```php
function map(callable $closure): static<T>
```

Pass each item to a closure and create a new collection of results.

The closure must return a value of `T` or `\Generator<T>`:

```php
$collection->map(function(Product $product) {
    // do something
    return $product;
});
```

### reduce

```php
function reduce(callable $closure, $initial = null): mixed|null
```

Reduces the collection to a single value:

```php
$collection = new Collection([new Product('foo', 10), new Product('bar', 2)]);

$amount = $collection->reduce(function($carry, Product $product) {
    return $carry + $product->amount;
});

// 4
```

### slice

```php
function slice(int $first, ?int $length = null): static<T>
```

Create a new collection with a slice of the current one:

```php
$collection->slice(3);
```

### copy

```php
function copy(): static<T>
```

Create a new collection with the same elements (alias for `slice(0)`):

```php
$collection->copy();
```

### chunk

```php
function chunk(int $length): \Generator<static<T>>
```

Breaks the collection into multiple collections of a given length. The last one may contain fewer elements:

```php
$collection->chunk(10);
```

### filter

```php
function filter(callable $closure): static<T>
```

Pass each item to a closure and create a new collection of items for which its result will be `true`.

The closure must return a `bool` value:

```php
$collection->filter(function(Product $product){
    return $product->amount > 1;
});
```

### toArray

```php
function toArray(?callable $closure = null): array
```
Return all items of a collection. You can transform every element of array via a closure. If you need an associative array, the closure should return a generator yielding a key/value pair.

The closure must return any value or `\Generator<array-key,mixed>`:

```php
$collection->toArray(); // [Product, Product]

$collection->toArray(function(Product $product) {
    return $product->title;
}); // ['foo', 'bar']

$collection->toArray(function(Product $product): \Generator {
    yield $product->title => $product->amount;
}); // ['foo'=>10, 'bar'=>2]
```
