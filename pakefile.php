<?php

require(__DIR__ . "/vendor/autoload.php");
use Illuminate\Database\Capsule\Manager as Capsule;

pake_task("default");
function run_default() {
	
}

/**
 * Runs the first time setup
 */
pake_task("setup");
pake_desc("Runs the one time setup operations for your hackstack");
function run_setup() {
	$helper = \Hackstack\Helpers\PakeHelper::getInstance();
	$root = $helper->getAppRoot();
	$currentDatetime = new DateTime();

	$helper->status($helper::ONE, "Verifying setup has not already occurred");

	// Check for the hackstack.lock file
	if(!file_exists(__DIR__ . "/hackstack.lock")) {
		$helper->status($helper::TWO, "No lock file found, continuing with first time setup");
		
		// Setup the database
		$helper->status($helper::TWO, "Looking for configuration file and using to create a database connection");
		try {
			$db = \Hackstack\Helpers\DatabaseHelper::getInstance();
			$helper->status($helper::THREE, "Connection established, dropping the {hackstack} database and rebuilding it");
			
			Capsule::statement("DROP DATABASE hackstack;");
			Capsule::statement("CREATE DATABASE hackstack;");

			$helper->status($helper::THREE, "Starting Sentry setup");
			if(file_exists($root . "/vendor/cartalyst/Sentry/schema/mysql.sql")) {
				// Run the sentry script
				Capsule::connection()->getPdo()->exec("USE hackstack;" . file_get_contents($root . "/vendor/cartalyst/sentry/schema/mysql.sql"));
				$helper->status($helper::THREE, "Sentry tables have been built!");

				$helper->status($helper::THREE, "Adding username support to Sentry. You can make this the default login identifier in the Sentry config file.");
				Capsule::connection()->getPdo()->exec("USE hackstack;" . file_get_contents($root . "/configuration/scripts/sql/ADD_USERNAME_SUPPORT_TO_SENTRY.sql"));

				// Prompt to fill with test data

				// Setup log directory symlinks
				$helper->status($helper::TWO, "Beginning setup of log file symlinks");
				$logFileBase = __DIR__ . "/logs/" . $currentDatetime->format("Ymd");
				// Latest access log
				if(!file_exists($logFileBase . "-access.log")) {
					file_put_contents($logFileBase . "-access.log", "");
				}

				if(file_exists(__DIR__ . "/logs/access")) {
					unlink(__DIR__ . "/logs/access");
				}
				$success = symlink($logFileBase . "-access.log", __DIR__ . "/logs/access");
				if($success) {
					$helper->status($helper::THREE, "Symlink created in the logs directory for the access log called 'access'");
				} else {
					$helper->status($helper::THREE, "Failed to create the access log symlink", 'ERROR');
				}

				// Latest error log
				if(!file_exists($logFileBase . "-error.log")) {
					file_put_contents($logFileBase . "-error.log", "");
				}
				
				if(file_exists(__DIR__ . "/logs/error")) {
					unlink(__DIR__ . "/logs/error");
				}
				$success = symlink($logFileBase . "-error.log", __DIR__ . "/logs/error");
				if($success) {
					$helper->status($helper::THREE, "Symlink created in the logs directory for the error log called 'error'");
				} else {
					$helper->status($helper::THREE, "Failed to create the error log symlink", 'ERROR');
				}

				// Setup logrotate
				// Setup cron (if any)

				// Create hackstack.lock
				file_put_contents(__DIR__ . "/hackstack.lock", "Lock file generated " . $currentDatetime->format("Y-m-d H:i:s"));
				$helper->status($helper::ONE, "Lockfile generated. Setup completed.");
			} else {
				$helper->status($helper::THREE, "No Sentry build script exists at {" . $root . "/vendor/cartalyst/Sentry/schema/mysql.sql}", "ERROR");
			}
		} catch(Exception $e) {
			$helper->status($helper::THREE, $e->getMessage(), 'ERROR');
			$helper->status($helper::TWO, "Abandoning setup", 'ERROR');
		}
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