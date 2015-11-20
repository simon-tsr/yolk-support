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

use yolk\contracts\support\Config;
use yolk\contracts\support\Arrayable;

/**
 * Simple PHP array-based, read-only configuration class.
 * 
 * Config files are simply PHP files that define arrays of key/value pairs.
 * $config = array(
 *    'logs' => array(
 *       'debug' => '/var/log/my_app/debug.log',
 *       'error' => '/var/log/my_app/error.log',
 *    ),
 *    'debug' => true
 * };
 *
 * Supports accessing nested keys using dot-notation:
 * * 'logs' will return all defined logs
 * * 'logs.debug' will return the debug log definition
 *
 */
class BaseConfig extends collections\BaseDictionary implements Config {

	public function load( $file, $key = '' ) {

		$config = [];

		require $file;

		if( !isset($config) || !is_array($config) )
			throw new \LogicException('Invalid Configuration');

		if( empty($key)  )
			$this->merge($config);
		else
			$this->set($key, $config);

		return $this;

	}

	public function has( $key ) {
		return $this->get($key) !== null;
	}

	public function get( $key, $default = null ) {
		
		$parts   = explode('.', $key);
		$context = &$this->items;

		foreach( $parts as $part ) {
			if( !isset($context[$part]) ) {
				return $default;
			}
			$context = &$context[$part];
		}

		return $context;

	}

	public function set( $key, $value ) {

		$parts   = explode('.', $key);
		$count   = count($parts) - 1;
		$context = &$this->items;

		for( $i = 0; $i <= $count; $i++ ) {
			$part = $parts[$i];
			if( !isset($context[$part]) && ($i < $count) ) {
				$context[$part] = [];
			}
			elseif( $i == $count ) {
				$context[$part] = $value;
				if( $parts[0] == 'php' ) {
					ini_set($part, $value);
				}
				return $this;
			}
			$context = &$context[$part];
		}

		return $this;

	}

	public function merge( array $config ) {

		foreach( $config as $k => $v ) {
			$this->set($k, $v);
		}

		return $this;

	}

}

// EOF