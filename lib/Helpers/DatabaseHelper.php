<?php

namespace Hackstack\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Connects to the DB using Illuminate and the connection details in the yaml configuration file
 */
class DatabaseHelper extends \Hackstack\Helpers\Helper {

	/**
	 * Instance of the singleton database Capsule
	 * @var instance Illuminate\Database\Capsule\Manager
	 */
	private static $db;

	protected function __construct() {
		parent::__construct();
		self::$db = new Capsule();

		// Find the yaml configuration file
		if(file_exists(self::$AppRoot . "/configuration/databases.yml")) {
			$config = \pakeYaml::loadFile(self::$AppRoot . "/configuration/databases.yml");

			if(isset($config["development"]) && !empty($config["development"])) {
				self::$db->addConnection($config["development"]);
				// Register as global instance so it initializes static accessors
				self::$db->setAsGlobal();
			} else {
				throw new \Exception("No database config for the {development} environment");
			}
		} else {
			throw new \Exception("No database yaml config file present at {" . self::$AppRoot . "/configuration/databases.yml}");
		}
	}
}

?>