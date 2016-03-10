<?php
/*
 * This file is part of Yolk - Gamer Network's PHP Framework.
 *
 * Copyright (c) 2015 Gamer Network Ltd.
 *
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/gamernetwork/yolk-support
 */

namespace yolk\support;

use yolk\contracts\support\Error;
use yolk\contracts\support\Type;

/**
 * Definition of an field.
 */
class Field {

	protected $name;

	protected $type;

	protected $required;

	protected $nullable;

	protected $default;

    protected $label;

	protected $rules;

	public function __construct( $name, $type = Type::TEXT, array $rules = [] ) {

		$this->name = $name;
		$this->type = $type;

		// default rules
		$defaults = [
			'required' => false,
			'nullable' => false,
			'default'  => '',
            'label'    => $name,
		];
		$rules += $defaults;

		$this->processRules($rules);

	}

	public function __get( $key ) {
		if( $key == 'default' )
			return $this->getDefault();
		elseif( isset($this->rules[$key]) )
			return $this->rules[$key];
		elseif( isset($this->$key) )
			return $this->$key;
		else
			return null;
	}

	public function __isset( $key ) {
		return isset($this->$key) || isset($this->rules[$key]);
	}

    public function toArray() {
        return [
            'name'     => $this->name,
            'type'     => $this->type,
            'required' => $this->required,
            'nullable' => $this->nullable,
            'default'  => $this->default,
            'label'    => $this->label,
            'rules'    => $this->rules,
        ];
    }

	public function isNumeric() {
		return in_array($this->type, [
			Type::INTEGER,
			Type::FLOAT
		]);
	}

	public function isTemporal() {
		return in_array($this->type, [
			Type::DATETIME,
			Type::DATE,
			Type::TIME,
			Type::YEAR,
		]);
	}

	public function isText() {
		return in_array($this->type, [
			Type::TEXT,
			Type::IP,
			Type::EMAIL,
			Type::URL,
		]);
	}

	public function isJSON() {
		return $this->type == Type::JSON;
	}

	public function isObject() {
		return in_array($this->type, [
			Type::OBJECT,
			Type::ENTITY,
		]);
	}

	public function isEntity() {
		return $this->type == Type::ENTITY;
	}

	public function isCollection() {
		return $this->type == Type::COLLECTION;
	}

	public function isUnique() {
		return !empty($this->rules['unique']);
	}

	public function cast( $v ) {

		switch( $this->type ) {
			case Type::INTEGER:
			case Type::TIMESTAMP:
			case Type::YEAR:
				return (int) $v;

			case Type::FLOAT:
				return (float) $v;

			case Type::BOOLEAN:
				return (bool) $v;

			case Type::DATETIME:
			case Type::DATE:
				return preg_match('/0000-00-00/', $v) ? '' : $v;

			case Type::JSON:
				if( !$v )
					$v = [];
				elseif( !is_array($v) && is_array($arr = json_decode($v, true)) )
					$v = $arr;
				return $v;

			default:
				return $v;
		}

	}

	public function validate( $v ) {

		if( $this->required && Validator::isEmpty($v, $this->type) )
			return [$v, Error::REQUIRED];

		elseif( !$this->nullable && ($v === null) )
			return [$v, Error::NULL];

		list($clean, $error) = $this->validateType($v, $this->type);

		if( $error )
			return [$v, $error];
		else
			return $this->validateRules($clean);

	}

	/**
	 * Return the fields default value.
	 * @return mixed
	 */
	protected function getDefault() {
		$default = $this->default;
		if( $default instanceof \Closure )
			return $default();
		else
			return $this->cast($default);
	}

	protected function validateType( $v, $type ) {

		// Innocent until proven guilty
		$error = Error::NONE;
		$clean = $v;

		$validators = [
			Type::TEXT     => 'validateText',
			Type::INTEGER  => 'validateInteger',
			Type::FLOAT    => 'validateFloat',
			Type::BOOLEAN  => 'validateBoolean',
			Type::DATETIME => 'validateDateTime',
			Type::DATE     => 'validateDate',
			Type::TIME     => 'validateTime',
			Type::YEAR     => 'validateYear',
			Type::EMAIL    => 'validateEmail',
			Type::URL      => 'validateURL',
			Type::IP       => 'validateIP',
			Type::JSON     => 'validateJSON',
		];

		if( isset($validators[$type]) ) {
			$method = $validators[$type];
			$clean = Validator::$method($v);
		}
		elseif( in_array($type, [Type::OBJECT, Type::ENTITY]) ){
			$clean = Validator::validateObject($v, $this->rules['class'], $this->nullable);
		}
		elseif( $type == Type::BINARY ) {
			$clean = (string) $v;
		}

		// boolean fields will be null on error
		if( ($clean === false) || (($type == Type::BOOLEAN) && ($clean === null)) )
			$error = Error::getTypeError($type);

		return [$clean, $error];

	}

	protected function validateRules( $v ) {

		if( $error = $this->validateRange($v) )
			return [$v, $error];

		elseif( $error = $this->validateLength($v) )
			return [$v, $error];

		elseif( $error = $this->validateValues($v) )
			return [$v, $error];

		elseif( $error = $this->validateRegex($v) )
			return [$v, $error];

		return [$v, $error];

	}

	protected function validateRange( $v ) {

		if( isset($this->rules['min']) && $v && ($v < $this->rules['min']) )
			return Error::MIN;

		if( isset($this->rules['max']) && $v && ($v > $this->rules['max']) )
			return Error::MAX;

		return Error::NONE;

	}

	protected function validateLength( $v ) {

		if( isset($this->rules['min_length']) && mb_strlen($v) < $this->rules['min_length'] )
			return Error::TOO_SHORT;

		if( isset($this->rules['max_length']) && mb_strlen($v) > $this->rules['max_length'] )
			return Error::TOO_LONG;

		return Error::NONE;

	}

	protected function validateRegex( $v ) {

		if( isset($this->rules['regex']) && !preg_match($this->rules['regex'], $v) )
			return Error::REGEX;

		return Error::NONE;

	}

	protected function validateValues( $v ) {

		if( isset($this->rules['values']) && !in_array($v, $this->rules['values']) )
			return Error::VALUE;

		return Error::NONE;

	}

	protected function processRules( array $rules, $container = 'array' ) {

		$this->required = $rules['required'];
		$this->nullable = $rules['nullable'];
		$this->default  = $rules['default'];
        $this->label    = $rules['label'];

		unset(
			$rules['required'],
			$rules['nullable'],
			$rules['default'],
			$rules['label']
		);

		if( $this->isObject() ) {

			// object fields must specify a class
			if( empty($rules['class']) )
				throw new \LogicException("Missing class name for item: {$this->name}");

			// object fields are nullable by default
			$this->nullable = true;
			$this->default  = null;

		}
		elseif( $this->isCollection() ) {

			// collection fields must specify a class
			if( empty($rules['class']) )
				throw new \LogicException("Missing item class for collection: {$this->name}");

			$rules['container'] = empty($rules['container']) ? $container : $rules['container'];

			// replace default with closure to generate a new collection
			$this->default = function() use ($rules) {

				$container = $rules['container'];

				if( $container == 'array' )
					return [];
				else
					return new $container($rules['class']);

			};

		}

		$this->rules = $rules;

	}

}
