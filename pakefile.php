<?php

/**
 * Internal singleton class that provides some useful helpers and configurations
 */
class HackStackHelper {

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
	 * @var instance 	HackStackHelper
	 */
	private static $instance;

	private function __construct() {}

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
	 * Helper to output a status message, formatted according to message child depth and logging level
	 */
	public function status($multiplier, $message, $level = 'INFO') {
		$linePrefix = "> ";
		if(is_integer($multiplier) && ($multiplier > 0)) {
			$linePrefix = (str_repeat(" ", $multiplier) . ">" . str_repeat(">", $multiplier / 2) . " ");
		}

		echo pakeColor::colorize($linePrefix . $message, $level) . "\n";
	}
}

pake_task("default");
function run_default() {
	
}

/**
 * Runs the first time setup
 */
pake_task("setup");
pake_desc("Runs the one time setup operations for your hackstack");
function run_setup() {
	$HSH = HackStackHelper::getInstance();

	$HSH->status($HSH::ONE, "Verifying setup has not already occurred");

	// Check for the hackstack.lock file
	if(!file_exists(__DIR__ . "/hackstack.lock")) {
		$HSH->status($HSH::TWO, "No lock file found, continuing with first time setup");
		// Find the yaml configuration file
		if(file_exists(__DIR__ . "/configuration/databases.yml")) {
			$configurations = pakeYaml::loadFile(__DIR__ . "/configuration/databases.yml");
			if(isset($configurations["developmen"]) && !empty($configurations["development"])) {

			} else {
				$HSH->status($HSH::TWO, "No database config for the {development} environment", 'ERROR');
			}
		} else {
			$HSH->status($HSH::TWO, "No database yaml config file present at {" . __DIR__ . "/configuration/databases.yml}", 'ERROR');
			$HSH->status($HSH::TWO, "Abandoning setup", 'ERROR');
		}
		// Setup the database

			// Run the sentry script
			// Prompt to fill with test data
		// Setup log directory symlink for
			// Latest access log
			// Latest error log
		// Setup logrotate
		// Setup cron (if any)
		// Create hackstack.lock
	} else {
		pake_echo_error("Aborting initial setup to avoid potential data loss");
		pake_echo_error("The hackstack.lock file exists in the root; this means initial setup has already been run. Remove this file if you would like to re-run initial setup");
	}
		


}

pake_task("rebuild");
pake_desc("Rebuilds the database to a fresh setup state");
function run_rebuild() {
	pake_echo_comment("Running the 'rebuild' task to recreate the databases");

}

pake_task("routes");
pake_desc("Lists all of the available routes currently setup along with what request types they respond to");
function run_routes() {
	pake_echo_comment("Finding all configured routes");
}

pake_task("status");
function run_status() {
	pake_echo_comment("Finding all configured routes");
}

pake_task("deploy");
pake_desc("Deploys your current hackstack to a configured server setup");
function run_deploy() {
	pake_echo_comment("Deploying your hackstack to the configured '' environment");
}