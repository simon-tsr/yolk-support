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

namespace yolk\support\collections;

use yolk\contracts\support\Arrayable;
use yolk\contracts\support\collections\Collection;

/**
 * Basic collection implementation.
 * Should not be implemented directly, instead use one of the more specific
 * sub-classes, such as BaseSet or BaseDictionary
 */
class BaseCollection implements \IteratorAggregate, Collection, Arrayable {

	protected $items;

	public function __construct( array $items = [] ) {
		$this->items = $items;
	}

	public function count() {
		return count($this->items);
	}

	public function isEmpty() {
		return count($this->items) == 0;
	}

	public function clear() {
		$this->items = [];
		return $this;
	}

	public function contains( $item ) {
		return array_search($item, $this->items) !== false;
	}

	public function toArray() {
		return $this->items;
	}

	public function getIterator() {
		return new \ArrayIterator($this->items);
	}

}

// EOF