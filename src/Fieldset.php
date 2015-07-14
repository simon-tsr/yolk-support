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

/**
 * A set of field definitions and associated validation rules.
 */
class Fieldset implements \IteratorAggregate, \Countable {

	/**
	 * Array of fields defined in this fieldset.
	 * @var array
	 */
	protected $fields;

	public function __construct() {
		$this->fields = [];
	}

	/**
	 * Add a new field definition.
	 * @param  string   $name    name of the field
	 * @param  string   $type    one of the \yolk\contracts\support\Type constants or a string containing a custom type
	 * @param  array    $rules   an array of validation rules
	 * @return self
	 */
	public function add( $name, $type = Type::TEXT, $rules = [] ) {
		$this->fields[$name] = new Field($name, $type, $rules);
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
