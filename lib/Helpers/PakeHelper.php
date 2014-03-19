<?php

namespace Hackstack\Helpers;

/**
 * Internal singleton class that provides some useful helpers and config
 */
class PakeHelper {

	/**
	 * Constants to control the status message spacing multipliers
	 */
	const ONE = 0;
	const TWO = 2;
	const THREE = 4;
	const FOUR = 6;
	const FIVE = 8;

	/**
	 * Instance of the singleton helper
	 * @var instance HackStackHelper
	 */
	private static $instance;

	/**
	 * Directory path to the hackstack application root
	 * @var String
	 */
	private $AppRoot;

	private function __construct() {
		$this->AppRoot = dirname(dirname(__DIR__));
	}

	/**
	 * Returns the current instance and initializes a new one if it doesnt exist
	 * @return HackStackHelper
	 */
	public static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns the directory path to the hackstack root
	 * @return String
	 */
	public function getAppRoot() {
		return $this->AppRoot;
	}

	/**
	 * Helper to output a status message, formatted according to message child depth and logging level
	 */
	public function status($multiplier, $message, $level = 'INFO') {
		$linePrefix = "> ";
		if(is_integer($multiplier) && ($multiplier > 0)) {
			$linePrefix = (str_repeat(" ", $multiplier) . ">" . str_repeat(">", $multiplier / 2) . " ");
		}

		echo \pakeColor::colorize($linePrefix . $message, $level) . "\n";
	}
}

?>