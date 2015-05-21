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

use yolk\contracts\support\collections\Set;

/**
 * Basic set implementation.
 */
class BaseSet extends BaseCollection implements Set {

	public function add( $item ) {

		$new = !$this->contains($item);

		if( $new )
			$this->items[] = $item;

		return $new;
	}

	public function remove( $item ) {

		$key = array_search($item, $this->items);

		if( $key !== false )
			unset($this->items[$key]);

		return $key !== false;

	}

}

// EOF