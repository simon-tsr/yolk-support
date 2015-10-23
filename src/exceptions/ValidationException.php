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

namespace yolk\support\Exceptions;

class ValidationException extends \DomainException {

	protected $errors = [];

	public function __construct( array $errors, $source, \Exception $previous = null ) {
		parent::__construct("Validation Error: {$source}");
		$this->errors = $errors;
	}

	public function getErrors() {
		return $this->errors;
	}

}

// EOF