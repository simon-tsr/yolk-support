<?php
/*
 * This file is part of Yolk - Gamer Network's PHP Framework.
 *
 * Copyright (c) 2013 Gamer Network Ltd.
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/gamernetwork/yolk-support
 */

namespace yolk\support;

use yolk\contracts\database\Query;
use yolk\contracts\support\Filter;
use yolk\contracts\support\Arrayable;

/**
 * Generic implementation of \yolk\contracts\support\Filter.
 */
class GenericFilter implements Filter, Arrayable {

	protected $criteria;

	protected $offset;

	protected $limit;

	protected $orderby;

	public function __construct() {
		$this->clear();
	}

	public function getCriteria() {
		return $this->criteria;
	}

	public function getLimit() {
		return $this->limit ?: PHP_INT_MAX;
	}

	public function getOffset() {
		return $this->offset;
	}

	public function getOrder() {
		return $this->orderby;
	}

	public function toArray() {
		return array(
			'criteria' => $this->criteria,
			'offset'   => $this->offset,
			'limit'    => $this->limit,
			'orderby'  => $this->orderby,
		);
	}

	/**
	 * Remove all existing criteria from the filter.
	 * @return self
	 */
	public function clear( $what = [] ) {

		$defaults = [
			'criteria'  => [],
			'offset'    => 0,
			'limit'     => 0,
			'orderby'   => [],
		];

		$what = (array) $what;

		if( empty($what) )
			$what = array_keys($defaults);

		foreach( $what as $k ) {
			$this->$k = $defaults[$k];
		}

		return $this;

	}

	/**
	 * $field must equal $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function equals( $field, $value ) {
		return $this->addCriteria($field, '=', $value);
	}

	/**
	 * $field must not equal $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function notEquals( $field, $value ) {
		return $this->addCriteria($field, '!=', $value);
	}

	/**
	 * $field must be less than $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function lessThan( $field, $value ) {
		return $this->addCriteria($field, '<', $value);
	}

	/**
	 * $field must be greater than $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function greaterThan( $field, $value ) {
		return $this->addCriteria($field, '>', $value);
	}

	/**
	 * $field must be less than or equal to $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function equalsLessThan( $field, $value ) {
		return $this->addCriteria($field, '<=', $value);
	}

	/**
	 * $field must greater than or equal to $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function equalsGreaterThan( $field, $value ) {
		return $this->addCriteria($field, '>=', $value);
	}

	/**
	 * $field must match the pattern specified in $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value   pattern to match
	 * @return self
	 */
	public function like( $field, $value ) {
		return $this->addCriteria($field, 'LIKE', $value);
	}

	/**
	 * $field must not match the pattern specified in $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value   pattern to match
	 * @return self
	 */
	public function notLike( $field, $value ) {
		return $this->addCriteria($field, 'NOT LIKE', $value);
	}

	/**
	 * $field must equal one of the items in $values.
	 * @param  string         $field     name of the field
	 * @param  array          $values    values to match
	 * @return self
	 */
	public function in( $field, array $values ) {
		return $this->addCriteria($field, 'IN', $values);
	}

	/**
	 * $field must not equal one of the items in $values.
	 * @param  string         $field     name of the field
	 * @param  array          $values    values to exclude
	 * @return self
	 */
	public function notIn( $field, array $values ) {
		return $this->addCriteria($field, 'NOT IN', $values);
	}

	/**
	 * Specify a field to order the results by.
	 * Multiple levels of ordering can be specified by calling this method multiple times.
	 * @param  string   $field   name of the field
	 * @param  boolean  $dir     true to sort ascending, false to sort descending
	 * @return self
	 */
	public function orderBy( $field, $dir = Filter::SORT_ASC ) {
	
		$field = trim($field);

		if( empty($field) )
			throw new \InvalidArgumentException('No field specified');

		$modifier = substr($field, 0, 1);

		if( in_array($modifier, ['+', '-']) ) {
			$dir = ($modifier == '+');
			$field = substr($field, 1);
		}

		$this->orderby[$field] = $dir;

		return $this;

	}

	/**
	 * Specify an offset into the resultset that results should be returned from.
	 * @param  integer  $offset
	 * @return self
	 */
	public function offset( $offset ) {
		$this->offset = max(0, (int) $offset);
		return $this;
	}

	/**
	 * Specify a limit to the number of results returned.
	 * @param  integer  $limit
	 * @return self
	 */
	public function limit( $limit )	 {
		$this->limit = max(1, (int) $limit);
		return $this;
	}

	/**
	 * Apply the filter to a database Query
	 * @param  Query  $query
	 * @param  array  $columns a map of field names to database column names
	 * @return Query
	 */
	public function toQuery( Query $query, array $columns = [], $alias = '' ) {

		foreach( $this->criteria as $column => $criteria ) {

			if( !$column = $this->getColumnName($columns, $column, $alias) )
				continue;

			foreach( $criteria as $operator => $value ) {
				$query->where($column, $operator, $value);
			}

		}

		foreach( $this->getOrder() as $column => $ascending ) {
			$column = $this->getColumnName($columns, $column, $alias);
			if( !$column )
				continue;
			$query->orderBy($column, $ascending);
		}

		if( $this->offset )
			$query->offset($this->offset);

		if( $this->limit )
			$query->limit($this->limit);

		return $query;

	}

	protected function getColumnName( $columns, $column, $alias ) {

		$column = isset($columns[$column]) ? $columns[$column] : $column;

		if( !$column )
			return '';

		elseif( strpos($column, '.') )
			return $column;

		elseif( $alias )
			return "{$alias}.{$column}";

		return $column;

	}

	/**
	 * Add a criteria item to the filter.
	 * @param  string   $field
	 * @param  string   $operator
	 * @param  mixed    $value
	 * @return self
	 */
	protected function addCriteria( $field, $operator, $value ) {

		$field = trim($field);

		if( !isset($this->criteria[$field]) )
			$this->criteria[$field] = [];

		$this->criteria[$field][$operator] = $value;

		return $this;

	}

}

// EOF