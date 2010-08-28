<?php

/**
 * MHP class
 * @constructor
 * @extends UGC
 */
class MHP extends UGC {

	public
		$config,
		$onload = Array();

	/**
	 * MHP constructor
	 * @param array $config The configuration associative array
	 * @member MHP
	 * @public
	 */
	function __construct($config) {

		parent::__construct($config);

		$this->config = &$config;

		$this->pages = Array(
			'api',
			'error',
			'home',
			'room'
		);

	}

}
