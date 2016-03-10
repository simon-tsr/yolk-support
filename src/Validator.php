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

use yolk\contracts\orm\Entity;
use yolk\contracts\support\Type;

class Validator {

	private function __construct() {}

	/**
	 * Short-cut methods for determining if a value is of a particular type.
	 * @param  string $name
	 * @param  array $args array of arguments pass to the function
	 * @return boolean
	 */
	public static function __callStatic( $name, $args ) {

		$name = 'validate'. substr($name, 2);

		if( !method_exists(__CLASS__, $name) )
			throw new \BadMethodCallException(sprintf("%s::%s()", get_called_class(), $name));

		$failure = ($name == 'validateBoolean') ? null : false;

		switch( count($args) ) {
			case 1:
				return static::$name($args[0]) !== $failure;
			case 2:
				return static::$name($args[0], $args[1]) !== $failure;
			default:
				return call_user_func_array([get_called_class(), $name], $args) !== $failure;
		}

	}


	public static function isEmpty( $v, $type = Type::TEXT ) {

		if( !$v || (is_scalar($v) && !trim($v)) )
			return true;

		switch( $type ) {

			case Type::COLLECTION:
				return count(array_filter($v)) == 0;

			case Type::ENTITY:
				return is_object($v) && !(bool) $v->id;

			case Type::DATETIME:
			case Type::DATE:
			case Type::TIME:
			case Type::YEAR:
				return !preg_match('/(0000-00-00)|(00:00:00)|(0000)/', $v);

			case Type::IP:
				return preg_match('/0.0.0.0/', $v) || (is_numeric($v) && !(int) $v);

			default:

		}

		return false;

	}

	/**
	 * Validates a string has the correct encoding and trims whitespace.
	 * @param  string $v
	 * @param  string $encoding
	 * @return string|boolean Returns the trimmed string or false if incorrect encoding
	 */
	public static function validateText( $v, $encoding = 'UTF-8' ) {
		return mb_check_encoding($v, $encoding) ? trim($v) : false;
	}

	/**
	 * Validates a value as an integer.
	 * Null, false and empty strings are converted to zero.
	 * @param  mixed   $v
	 * @return integer|false
	 */
	public static function validateInteger( $v ) {
		return $v ? filter_var($v, FILTER_VALIDATE_INT) : 0;
	}

	/**
	 * Validates a value as a float.
	 * Null, false and empty strings are converted to zero.
	 * @param  mixed   $v
	 * @return float|false
	 */
	public static function validateFloat( $v ) {
		return $v ? filter_var($v, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND) : 0;
	}

	/**
	 * Validates a value as a boolean.
	 * Recognises the following string: "1", "true", "on" and "yes".
	 * @param  mixed   $v
	 * @return boolean|null
	 */
	public static function validateBoolean( $v ) {
		// FILTER_VALIDATE_BOOLEAN will return null if passed an actual boolean false
		return ($v === false) ? $v : filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	}

	public static function validateTimestamp( $v ) {
		$ts = filter_var($v, FILTER_VALIDATE_INT);
		if( $ts === false )
			$ts = strtotime(str_replace('@', ' ', $v));
		return $ts;
	}

	public static function validateDateTime( $v, $format = 'Y-m-d H:i:s' ) {

		if( !trim($v) || preg_match('/0000-00-00/', $v) )
			return '';

		$ts = static::validateTimestamp($v);

		return ($ts === false) ? $ts : date($format, $ts);

	}

	public static function validateDate( $v ) {
		return static::validateDateTime($v, 'Y-m-d');
	}

	public static function validateTime( $v ) {
		return static::validateDateTime($v, 'H:i:s');
	}

	public static function validateYear( $v, $min = 1900, $max = 2155 ) {
		$v = static::validateInteger($v);
		return ($v >= $min) && ($v <= $max);
	}

	/**
	 * Validates a value as an ip4 address.
	 * @param  mixed   $v
	 * @return string
	 */
	public static function validateIP( $v ) {
		// if integer then convert to string
		if( $ip = filter_var($v, FILTER_VALIDATE_INT) )
			$v = long2ip($ip);
		return filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}

	/**
	 * Validates a value as an email address.
	 * Empty values are allowed and are converted to an empty string.
	 * @param  mixed   $v
	 * @return string|boolean
	 */
	public static function validateEmail( $v ) {
		return $v ? filter_var($v, FILTER_VALIDATE_EMAIL) : '';
	}

	/**
	 * Validates a value as a url.
	 * Empty values are allowed and are converted to an empty string.
	 * @param  mixed   $v
	 * @return string|boolean
	 */
	public static function validateURL( $v ) {
		return $v ? filter_var($v, FILTER_VALIDATE_URL) : '';
	}

	public static function validateJSON( $v ) {

		// if it's a string then try and decode it
		if( is_string($v) )
			$v = json_decode($v, true);
		// otherwise check we can encode it - we don't care about the function result
		else
			json_encode($v);

		return (json_last_error() === JSON_ERROR_NONE) ? $v : false;

	}

	public static function validateObject( $v, $class, $nullable = false ) {

		if( $v instanceof $class )
			return $v;

		elseif( $nullable && ($v === null) )
			return $v;

		return false;

	}

}

// EOF