
# Yolk Support

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gamernetwork/yolk-support/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/gamernetwork/yolk-support/?branch=develop)

Provides various supporting elements to the Yolk framework, such as:
* Collections
* Configuration
* Fieldsets and Fields
* Twig Extension
* Validation

## Requirements

This library requires only PHP 5.4 or later and the Yolk Contracts package (`gamernetwork/yolk-contracts`).

## Installation

It is installable and autoloadable via Composer as `gamernetwork/yolk-support`.

Alternatively, download a release or clone this repository, and add the `\yolk\support` namespace to an autoloader.

## License

Yolk Support is open-sourced software licensed under the MIT license.

## Collections

This package provides basic implementation of the `Collection`, `Dictionary` and
`Set` interfaces defined in the `yolk-contracts` package.

`BaseCollection` accepts an array of items in its constructor and provides basic
collection operations such as iteration, counting, emptying and testing for existence.
There is no support for adding or removing individual items, this being provided
by specific subclasses.

`BaseDictionary` extends `BaseCollection` to add support for adding and removing
key/value pairs - each key being unique.

`BaseSet` extends `BaseCollection` to add support for adding and removing
unique items.

```php
use yolk\support\collections\BaseCollection;
use yolk\support\collections\BaseDictionary;
use yolk\support\collections\BaseSet;

$c = new Collection([123, 456, 789]);

$c->count();		// returns 3
$c->contains(456);	// returns true
$c->contains(234);	// returns false
$c->isEmpty();		// returns false
$c->clear();		// remove all items
$c->isEmpty();		// returns true

$d = new BaseDictionary();

$d->add('foo', 123);
$d->add('bar', 456);
$d->has('foo');	// returns true
$d->has('baz');	// returns false
$d->get('foo');	// returns 123
$d->keys();		// returns ['foo', 'bar']
$d->remove('bar');

$d = new BaseSet();

$d->add('foo');
$d->add('bar');
$d->contains('foo');	// returns true
$d->remove('bar');
$d->contains('bar');	// returns false
```

## Configuration

`BaseConfig` provides a basic configuration object, the underlying implementation
being an array. The array can be populated from an external file via `load()`,
or from an existing array via `merge()`. Methods are also provided to allow easy
access to nested elements.

```php
use yolk\support\BaseConfig;

$c = new BaseConfig([
	'foo' => [
		'bar' => 123,
		'baz' => 456,
	],
]);

$c->get('foo.bar');	// returns 123

$c->set('foo.baz', 789);	// changes the value of ['foo']['bar']

// overwrite the 'foo' branch and add an additional value for 'bar'
$c->merge([
	'foo' => 'abc',
	'bar' => 'def',
]);

$c->get('foo.bar');	// returns null (key doesn't exist)

$c->get('bar');	// returns 'def'

$c->load(__DIR__. 'config.php');	// 'config.php' should create an array called $config
```

## Fieldsets and Fields

A field represents an object property, database column or similar construct.
Each field must have a name and a type and may optionally have additional rules
defining rules for values that are considered valid.

Supported field types are listed in `yolk\contracts\support\Type`.

```php
use yolk\support\Field;

$f = new Field('id', Type::INTEGER);
$f = new Field(
	'status',
	Type::ENUM,
	[
		'required' => true,
		'values' => ['EMPLOYEE', 'PARTNER', 'OTHER']
	]
);
$f = new Field('email', Type::EMAIL, ['required' => true, 'unique' => true]);
```

Available Rules:

* `required` - [`boolean`] if true, must have a non-empty value (default: false)
* `nullable` - [`boolean`] determines if null is an acceptable value (default: false)
* `default` - [`mixed`] default value to use if none specified (default: empty based on type)
* `label` - [`string`] user-friendly label, (default: title-cased name)
* `unique` - [`boolean`] determines if values should be unique (default: false)
* `min` - [`integer|string`] minimum acceptable value
* `max` - [`integer|string`] maximum acceptable value
* `min-length` - [`integer`] minimum length of value
* `max-length` - [`integer`] maximum length of value
* `regex` - [`string`] a regular expression that acceptable values must match
* `values` - [`array`] an array containing all acceptable values

## Twig Extension

## Validation

