<?php
/*
 * This file is part of Yolk - Gamer Network's PHP Framework.
 *
 * Copyright (c) 2014 Gamer Network Ltd.
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/gamernetwork/yolk
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

	protected $unique;

	protected $default;

    protected $label;

    protected $readonly;

    protected $guarded;

	protected $rules;

	public function __construct( $name, $type = Type::TEXT, array $rules = [] ) {

		$this->name = $name;
		$this->type = $type;
		
		// default rules
		$defaults = array(
			'required' => false,
			'nullable' => false,
			'unique'   => false,
			'default'  => '',
            'label'    => $name,
            'readonly' => false,
            'guarded'  => false,
		);
		$rules += $defaults;

		$this->processRules($rules);

	}

	public function __get( $key ) {
		if( $key == 'default' && ($this->default instanceof \Closure) )
			return $this->default();
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
            'unique'   => $this->unique,
            'default'  => $this->default,
            'label'    => $this->label,
            'readonly' => $this->readonly,
            'guarded'  => $this->guarded,
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
			Type::JSON,
		]);
	}

	public function isObject() {
		return $this->type == Type::OBJECT;
	}

	public function isCollection() {
		return $this->type == Type::COLLECTION;
	}

	public function cast( $v ) {

		switch( $this->type ) {
			case Type::INTEGER:
			case Type::TIMESTAMP:
			case Type::YEAR:
				return (int) $v;
			
			case Type::FLOAT :
				return (float) $v;
			
			case Type::BOOLEAN :
				return (bool) $v;
			
			case Type::DATETIME:
			case Type::DATE:
				return preg_match('/0000-00-00/', $v) ? '' : $v;
			
			case Type::JSON:
				if( !is_array($v) && is_array($arr = json_decode($v, true)) ) {
					$v = $arr;
				}
				return $v;

			default:
				return $v;
		}

	}

	public function validate( $v ) {

		if( $this->required && Validator::isEmpty($v) )
			return [$v, Error::REQUIRED];
		
		elseif( !$this->nullable && ($v === null) )
			return [$v, Error::NULL];

		list($clean, $error) = $this->validateType($v, $this->type);

		if( $error )
			return [$v, $error];
		else
			return $this->validateRules($clean);

	}

	protected function validateType( $v, $type ) {

		$error = Error::NONE;

		switch( $type ) {

			case Type::TEXT:
				$clean = trim((string) $v);
				break;

			case Type::INTEGER:
				$clean = Validator::validateInteger($v);
				break;

			case Type::FLOAT:
				$clean = Validator::validateFloat($v);
				break;

			case Type::BOOLEAN:
				$clean = Validator::validateBoolean($v);
				break;

			case Type::DATETIME:
				$clean = Validator::validateDateTime($v);
				break;

			case Type::DATE:
				$clean = Validator::validateDate($v);
				break;

			case Type::TIME:
				$clean = Validator::validateTime($v);
				break;

			case Type::YEAR:
				$clean = Validator::validateYear($v);
				break;

			case Type::EMAIL:
				$clean = Validator::validateEmail($v);
				break;

			case Type::URL:
				$clean = Validator::validateURL($v);
				break;

			case Type::IP:
				$clean = Validator::validateIP($v);
				break;

			case Type::JSON:
				$clean = Validator::validateJSON($v);
				break;

			case Type::OBJECT:
				$clean = Validator::validateObject($v, $this->rules['class']);
				break;

			case Type::BINARY:
				$clean = (string) $v;
				break;

			default:
				// Don't handle other types as they should be validated elsewhere
				$clean = $v;
				break;

		}

		// boolean fields will be null on error
		if( $type == Type::BOOLEAN )
			$error = ($clean === null) ? Error::BOOLEAN : Error::NONE;
		elseif( $clean === false )
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

	protected function processRules( array $rules ) {

		$this->required = $rules['required'];
		$this->nullable = $rules['nullable'];
		$this->unique   = $rules['unique'];
		$this->default  = $rules['default'];
        $this->label    = $rules['label'];
        $this->readonly = $rules['readonly'];
        $this->guarded  = $rules['guarded'];

		unset(
			$rules['required'],
			$rules['nullable'],
			$rules['unique'],
			$rules['default'],
			$rules['label'],
			$rules['readonly'],
			$rules['guarded']
		);

		if( $this->isCollection() ) {

			// collection fields must specify a class
			if( empty($rules['class']) )
				throw new \LogicException("Missing item class for collection: {$this->name}");

			$rules['collection'] = empty($rules['collection']) ? '\\yolk\\model\\RelatedCollection' : $rules['collection'];

			// replace default with closure to generate a new collection
			$this->default = function() use ($rules) {
				$collection = $rules['collection'];
				return new $collection($rules['class']);
			};

		}
		elseif( $this->isObject() ) {

			// object fields must specify a class
			if( empty($rules['class']) )
				throw new \LogicException("Missing class name for item: {$this->name}");

			// replace default with closure to generate a new object
			// TODO: should be generating a proxy?
			$this->default = function() use ($rules) {
				$object = $rules['class'];
				return new $object();
			};

		}

		$this->rules = $rules;

	}

}
