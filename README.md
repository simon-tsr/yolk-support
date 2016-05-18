Want to work for Gamer Network? [We are hiring!](http://www.gamesindustry.biz/jobs/gamer-network)

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

Fieldsets are collections of Fields and can be used as database table/model
definitions, html form definitions, etc.

### Fields

A Field must have a name and a type and may optionally have additional rules
defining rules for values that are considered valid.

Supported field types are listed in `yolk\contracts\support\Type`.

```php
use yolk\support\Field;

$f = new Field('id', Type::INTEGER);
$f = new Field('email', Type::EMAIL, ['required' => true, 'unique' => true]);
$f = new Field(
	'status',
	Type::ENUM,
	[
		'required' => true,
		'values' => ['EMPLOYEE', 'PARTNER', 'OTHER']
	]
);
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

Specified rules are available as properties on a Field object; e.g. `$f->required`.
Fields are immutable once instantiated.

There are two methods provided for validation:

* `cast()` - cast a specified value to the Field's type
* `validate()` - validate a specified value against the Field's rules and return
a 'cleaned' value and any validation error encountered. Supported errors are defined
in `yolk\contracts\support\Error`.

```php
use yolk\contracts\support\Error;
use yolk\support\Field;

$f = new Field('id', Type::INTEGER, ['required' => true, 'min' => 0]);

$f->cast(false);		// returns 0
$f->cast('123fgh');		// returns 123
$f->cast('fghsdfs');	// returns 0

$f->validate(0);			// returns [0, Error::REQUIRED]
$f->validate(false);		// returns [false, Error::REQUIRED]
$f->validate('123fgh');		// returns [123, Error::NONE]
$f->validate('fghsdfs');	// returns ['fghsdfs', Error::TYPE]
$f->validate(-123);			// returns [-123, Error::MIN]
```

### Fieldsets

Fields are added via the `add()` method:
```php
use yolk\support\Fieldset;

$f = new Fieldset();
$f->add($name, $type = Type::TEXT, $rules = []);
```

All Fields in the Fieldset can be validated at once by calling the `validate()` method
and passing an array of field names and values.
The return value is an array containing two elements, the first is an array of 'cleaned'
values, indexed by Field name; the second is an array of validation errors, indexed by
Field name.

```php
use yolk\contracts\support\Error;
use yolk\support\Fieldset;

$f = new Fieldset();

$f->add('id', Type::INTEGER);
$f->add('email', Type::EMAIL, ['required' => true, 'unique' => true]);


$f->validate([
	'id'    => '123',
	'email' => 'bar',
]);
/*
returns:
[
	[
		'id'    => 123,
		'email' => 'bar',
],
	[
		'id'    => Error::NONE,
		'email' => Error::EMAIL,
	],
]
```

## Twig Extension

The Twig extension provides additional utility functionality to Twig environments.

### Tests (is):
* `numeric` - determines if a variable contains a numeric value
* `integer` - determines if a variable contains an integer value
* `string`  - determines if a variable is a string
* `array`  - determines if a variable is an array
* `object`  - determines if a variable is an object

### Filters

* `md5` - generate the md5 hash of a value
* `sha1` - generate the sha1 hash of a value
* `truncate` - truncate a value to a specified length
* `sum` - generate the sum of an array, via `array_sum()`
* `shuffle` - shuffle an array, via `shuffle()`

### Functions

* `ceil` - round a value up to next highest integer, via `ceil()`
* `floor` - round a value down to next lowest integer, via `floor()`

If the `yolk-core` package is detected, most Helper methods are included as
filters or functions where there is no corresponding native functionality.

## Validation

The `yolk\support\Validator` class contains static methods for validating values.
Where possible validation is performed using PHP's built-in `filter_var()` function
with appropriate flags.
Validation function will return a valid value of the correct type, or an empty
value of the correct type.

* `isEmpty($v, $type = Type::TEXT)`
* `validateText($v)`
* `validateInteger($V)`
* `validateFloat($v)`
* `validateBoolean($v)`
* `validateTimestamp($v)`
* `validateDateTime($v, $format = 'Y-m-d H:i:s')`
* `validateDate($v)`
* `validateTime($v)`
* `validateYear($v, $min = 1900, $max = 2155)`
* `validateEmail($v)`
* `validateIP($v)`
* `validateURL($v)`
* `validateJSON($v)`
* `validateObject($v, $class, $nullable = false)`
