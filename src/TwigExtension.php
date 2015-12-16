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

class TwigExtension extends \Twig_Extension {

	public function getName() {
		return 'Yolk Twig Extension';
	}

	public function getTests() {
		return [
			new \Twig_SimpleTest('numeric', function( $value ) {
				return is_numeric($value);
			}),
			new \Twig_SimpleTest('integer', function( $value ) {
				return is_numeric($value) && ($value == (int) $value);
			}),
			new \Twig_SimpleTest('string', function( $value ) {
				return is_string($value);
			}),
			new \Twig_SimpleTest('array', function( $value ) {
				return is_array($value);
			}),
			new \Twig_SimpleTest('object', function( $value, $class = '' ) {
				$result = is_object($value);
				if( $class )
					$result = $result && ($value instanceof $class);
				return $result;
			}),
		];
	}

	public function getFilters() {

		$filters = [
			new \Twig_SimpleFilter('md5', 'md5'),
			new \Twig_SimpleFilter('sha1', 'sha1'),
			new \Twig_SimpleFilter('truncate', function( $str, $length, $replace = '...' ) {
		        if( isset($str) )
	        		return strlen($str) <= $length ? $str : substr($str, 0, $length - strip_tags($replace)) . $replace;
        		return null;
			}),
			new \Twig_SimpleFilter('sum', 'array_sum'),
			new \Twig_SimpleFilter('shuffle', 'shuffle'),
		];

		// include some helpful shit from yolk-core/StringHelper if we have it available
		if( class_exists ('\\yolk\\helpers\\StringHelper') ) {
			$filters = array_merge(
				$filters,
				[
					new \Twig_SimpleFilter('slugify', ['\\yolk\\helpers\\StringHelper', 'slugify']),
					new \Twig_SimpleFilter('uncamelise', ['\\yolk\\helpers\\StringHelper', 'uncamelise']),
					new \Twig_SimpleFilter('removeAccents', ['\\yolk\\helpers\\StringHelper', 'removeAccents']),
					new \Twig_SimpleFilter('ordinal', ['\\yolk\\helpers\\StringHelper', 'ordinal']),
					new \Twig_SimpleFilter('sizeFormat', ['\\yolk\\helpers\\StringHelper', 'sizeFormat']),
					new \Twig_SimpleFilter('stripControlChars', ['\\yolk\\helpers\\StringHelper', 'stripControlChars']),
					new \Twig_SimpleFilter('normaliseLineEndings', ['\\yolk\\helpers\\StringHelper', 'normaliseLineEndings']),
				]
			);
		}

		// more helpful shit from yolk-core/Inflector if we have it available
		if( class_exists ('\\yolk\\helpers\\Inflector') ) {
			$filters = array_merge(
				$filters,
				[
					new \Twig_SimpleFilter('pluralise', ['\\yolk\\helpers\\Inflector', 'pluralise']),
					new \Twig_SimpleFilter('singularise', ['\\yolk\\helpers\\Inflector', 'singularise']),
				]
			);
		}

		return $filters;

	}

	public function getFunctions() {

        $functions = [
            new \Twig_SimpleFunction('ceil', 'ceil'),
            new \Twig_SimpleFunction('floor', 'floor'),
        ];

		// include some helpful shit from yolk-core/StringHelper if we have it available
        if( class_exists ('\\yolk\\helpers\\StringHelper') ) {
			$functions = array_merge(
				$functions,
				[
		            new \Twig_SimpleFunction('randomHex', ['\\yolk\\helpers\\StringHelper', 'randomHex']),
		            new \Twig_SimpleFunction('randomString', ['\\yolk\\helpers\\StringHelper', 'randomString']),
				]
			);
		}

		return $functions;

	}

}

// EOF