<?php

namespace Hackstack\Helpers;

/**
 * Internal singleton class that provides some useful helpers and config
 */
class PakeHelper extends \Hackstack\Helpers\Helper {

	/**
	 * Constants to control the status message spacing multipliers
	 */
	const ONE = 0;
	const TWO = 2;
	const THREE = 4;
	const FOUR = 6;
	const FIVE = 8;

	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Returns the directory path to the hackstack root
	 * @return String
	 */
	public function getAppRoot() {
		return self::$AppRoot;
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