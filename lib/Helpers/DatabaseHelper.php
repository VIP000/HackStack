<?php

namespace Hackstack\Helpers;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Connects to the DB using Illuminate and the connection details in the yaml configuration file
 */
class DatabaseHelper {
	
	/**
	 * Instance of the singleton helper
	 * @var instance Hackstack\Helpers\DatabaseHelper
	 */
	private static $instance;

	/**
	 * Instance of the singleton database Capsule
	 * @var instance Illuminate\Database\Capsule\Manager
	 */
	private static $db;

	private function __construct() {
		self::$db = new Capsule();

		$root = dirname(dirname(__DIR__));

		// Find the yaml configuration file
		if(file_exists($root . "/configuration/databases.yml")) {
			$config = \pakeYaml::loadFile($root . "/configuration/databases.yml");

			if(isset($config["development"]) && !empty($config["development"])) {
				self::$db->addConnection($config["development"]);
				// Register as global instance so it initializes static accessors
				self::$db->setAsGlobal();
			} else {
				throw new \Exception("No database config for the {development} environment");
			}
		} else {
			throw new \Exception("No database yaml config file present at {" . $root . "/configuration/databases.yml}");
		}
	}

	/**
	 * Returns the current capsule instance and initializes a new one if it doesnt exist
	 * @return Illuminate\Database\Capsule\Manager
	 */
	public static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new self();
		}


		return self::$db;
	}


}

?>