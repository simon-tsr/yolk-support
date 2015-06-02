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

use yolk\contracts\support\collections\Dictionary;

/**
 * Basic dictionary implementation.
 */
class BaseDictionary extends BaseCollection implements Dictionary {

	public function has( $key ) {
		return array_key_exists($key, $this->items);
	}

	public function get( $key ) {
		return isset($this->items[$key]) ? $this->items[$key] : null;
	}

	public function add( $key, $item ) {

		$current = $this->get($key);

		$this->items[$key] = $item;

		return $current;

	}

	public function remove( $key ) {
		$current = $this->get($key);
		unset($this->items[$key]);
		return $current;
	}

	public function keys() {
		return array_keys($this->items);
	}


}

// EOF