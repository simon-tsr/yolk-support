<?php
/*
 * This file is part of Yolk - Gamer Network's PHP Framework.
 *
 * Copyright (c) 2013 Gamer Network Ltd.
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/gamernetwork/yolk
 */

namespace yolk\support;

use yolk\contracts\support\Type;
use yolk\contracts\support\Error;

/**
 * A set of field definitions and associated validation rules.
 * Fieldsets are used by Entities to provide structure and validation.
 * Note: getIterator defined in an ancestor, so instantiations are iterable,
 *	exposing field_name => metadata
 */
class Fieldset implements \IteratorAggregate, \Countable {

	/**
	 * Array of fields defined in this fieldset.
	 * @var arary
	 */
	protected $fields;

	/**
	 * Array of field names that are objects.
	 * @var array
	 */
	protected $objects;

	/**
	 * Array of field names that are collections.
	 * @var array
	 */
	protected $collections;

	/**
	 * Array of field names that must contain unique values.
	 * @var array
	 */
	protected $uniques;

	public function __construct() {
		$this->fields      = [];
		$this->objects     = [];
		$this->collections = [];
		$this->uniques     = [];
	}

	/**
	 * Add a new field definition.
	 * @param  string   $name    name of the field
	 * @param  string   $type    one of the Field::TYPE_* constants or a string containing a custom type
	 * @param  array    $rules   an array of validation rules
	 * @return self
	 */
	public function add( $name, $type = Type::TEXT, $rules = [] ) {

		$field = new Field($name, $type, $rules);

		$this->fields[$field->name] = $field;

		// remove the field from any existing sets - incase it's been redefined and no longer applies
		unset($this->objects[$field->name], $this->collections[$field->name], $this->uniques[$field->name]);

		if( $field->isObject() )
			$this->objects[$field->name] = true;
		elseif( $field->isCollection() )
			$this->collections[$field->name] = true;

		if( $field->unique )
			$this->uniques[$field->name] = true;

		return $this;

	}

	/**
	 * Validate the data in the specified array.
	 * @param  array $data
	 * @return array first element is an array of cleaned data, second is an array of the errors (if any)
	 */
	public function validate( array $data ) {

		$errors = [];

		foreach( $this->fields as $field ) {

			if( $field->isCollection() )
				continue;

			$f = $field->name;
			$v = isset($data[$f]) ? $data[$f] : null;

			list($clean, $errors[$f]) = $field->validate($v);

			if( !$errors[$f] )
				$data[$f] = $clean;

		}

		return [
			$data,
			array_filter($errors),
		];

	}

	public function listNames() {
		return array_keys($this->fields);
	}

	public function getDefaults() {
		$defaults = [];
		foreach( $this->fields as $field ) {
			$defaults[$field->name] = $field->default;
		}
		return $defaults;
	}

	/**
	 * Return a list of object fields.
	 * @return array
	 */
	public function getObjects() {
		return array_keys($this->objects);
	}

	/**
	 * Return a list of collection fields.
	 * @return array
	 */
	public function getCollections() {
		return array_keys($this->collections);
	}

	/**
	 * Return a list of unique fields.
	 * @return array
	 */
	public function getUniques() {
		return array_keys($this->uniques);
	}

	public function __get( $key ) {
		return isset($this->fields[$key]) ? $this->fields[$key] : null;
	}

	public function __isset( $key ) {
		return isset($this->fields[$key]);
	}

	public function getIterator() {
		return new \ArrayIterator($this->fields);
	}

	public function count() {
		return count($this->fields);
	}

}

// EOF
