<?php

namespace Hackstack\Helpers;

/**
 * Internal singleton class that provides some useful helpers and config
 */
abstract class Helper {

	/**
	 * Instance of the singleton helper
	 * @var instance HackStackHelper
	 */
	private static $HelperInstance;

	/**
	 * Directory path to the hackstack application root
	 * @var String
	 */
	protected static $AppRoot;

	protected function __construct() {
		self::$AppRoot = dirname(dirname(__DIR__));
	}

	/**
	 * Returns the current instance and initializes a new one if it doesnt exist
	 * @return extension of \Hackstack\Helpers\Helper
	 */
	public static function getInstance() {
		if(is_null(self::$HelperInstance)) {
			$class = get_called_class();
			self::$HelperInstance = new $class();
		}

		return self::$HelperInstance;
	}

}

?>