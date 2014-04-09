<?php

require(__DIR__ . "/vendor/autoload.php");
use Illuminate\Database\Capsule\Manager as Capsule;
use Cartalyst\Sentry\Facades\Native\Sentry as Sentry;

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
		
		$defaultAppName = "hackstack";
		$appConfig = \pakeYaml::loadFile($root . "/configuration/application.yml");
		if(!empty($appConfig["application"])) {
			if(!empty($appConfig["application"]["name"])) {
				# Set the default app name from the configuration
				$defaultAppName = $appConfig["application"]["name"];
			}
		}

		$helper->status($helper::TWO, "Choose an App Name. This will be used for logs, config files, and other named components.");
		$app_name = pake_input("What should your app name be?", $defaultAppName);
		
		$hackstackAppName = strtolower(preg_replace('/\s+/', '-', preg_replace('/[^a-zA-Z\s]*/', '', trim($app_name))));
		if(empty($hackstackAppName)) {
			$hackstackAppName = $defaultAppName;
		}
		$helper->status($helper::THREE, "App name will be [" . $hackstackAppName . "]");

		// Setup the database
		$helper->status($helper::TWO, "Looking for configuration file and using to create a database connection");
		try {
			$passwordChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%&*";
    		$password = substr(str_shuffle($passwordChars), 0, 25);

    		file_put_contents($root . "/configuration/databases.yml", str_replace(Array("<database>", "<password>"), Array($hackstackAppName, $password), file_get_contents($root . "/configuration/databases.yml")));

    		$helper->status($helper::THREE, "A random password has been set in the configuration/databases.yml file");

    		$helper->status($helper::THREE, "Connection established, dropping the {" . $hackstackAppName . "} database and rebuilding it");

    		$buildAppDatabaseStatements = Array(
    			"DROP DATABASE IF EXISTS " . $hackstackAppName,
    			"CREATE DATABASE " . $hackstackAppName,
    			"GRANT ALL PRIVILEGES ON " . $hackstackAppName . ".* TO 'hackstack'@'localhost' IDENTIFIED BY '" . $password . "'",
    			"FLUSH PRIVILEGES"
    		);
    		// Create the hackstack user and grant them privileges on the DB
    		file_put_contents($root . "/configuration/scripts/sql/SETUP_USER.sql", implode(";", $buildAppDatabaseStatements));
			pake_sh("mysql -u root -p < " . $root . "/configuration/scripts/sql/SETUP_USER.sql");

			$db = \Hackstack\Helpers\DatabaseHelper::getInstance();

			$helper->status($helper::THREE, "Starting Sentry setup");
			if(file_exists($root . "/vendor/cartalyst/sentry/schema/mysql.sql")) {
				// Run the sentry script
				Capsule::connection()->getPdo()->exec("USE '" . $hackstackAppName . "';" . file_get_contents($root . "/vendor/cartalyst/sentry/schema/mysql.sql"));
				$helper->status($helper::THREE, "Sentry tables have been built!");

				$helper->status($helper::THREE, "Adding username support to Sentry. You can make this the default login identifier in the Sentry config file.");
				Capsule::connection()->getPdo()->exec("USE '" . $hackstackAppName . "';" . file_get_contents($root . "/configuration/scripts/sql/ADD_USERNAME_SUPPORT_TO_SENTRY.sql"));

				$helper->status($helper::THREE, "Setting up the Sentry user groups");

				// Setup Sentry DB resolver
				Sentry::setupDatabaseResolver(Capsule::connection()->getPdo());
				
				// Create the Users group; suppress warning about sending data before session starts
				@Sentry::createGroup(Array(
					'name'        => 'Users',
					'permissions' => Array(
						'users' => 1
					)
				));
				$helper->status($helper::FOUR, "Created the 'Users' group");

				// Create the Admins group; suppress warning about sending data before session starts
				@Sentry::createGroup(Array(
					'name'        => 'Admins',
					'permissions' => Array(
						'admins' => 1,
						'users' => 1
					)
				));
				$helper->status($helper::FOUR, "Created the 'Admins' group");

				// Setup log directory symlinks
				$helper->status($helper::TWO, "Setting up log file symlinks");
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
					throw new \Exception("Failed to create the access log symlink");
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
					throw new \Exception("Failed to create the error log symlink");
				}

				// Setup logrotate
				// Setup cron (if any)

				// Setup self signed cert
				$helper->status($helper::TWO, "Setting up Apache self-signed cert");
				// Find the Apache directory
				$apacheDir = "";
				$sitesLocation = "";
				$apacheService = "";
				$defaultLocation = false;
				if(file_exists("/etc/apache2") && is_dir("/etc/apache2")) {
					// Ubuntu and others
					$apacheDir = "/etc/apache2";
					$sitesLocation = $apacheDir . "/sites-available";
					$apacheService = "service apache2";
					$defaultLocation = true;
				} else if(file_exists("/etc/apache") && is_dir("/etc/apache")) {
					$apacheDir = "/etc/apache";
					$sitesLocation = $apacheDir . "/sites-available";
					$apacheService = "service apache";
				} else if(file_exists("/etc/httpd") && is_dir("/etc/httpd")) {
					$apacheDir = "/etc/httpd";
					$sitesLocation = $apacheDir . "/conf.d";
					$apacheService = "service httpd";
				} else {
					throw new \Exception("Could not locate the Apache directory. Is it installed?");
				}

				if(!empty($apacheDir)) {
					if(!file_exists($apacheDir . "/ssl") || !is_dir($apacheDir . "/ssl")) {
						pake_superuser_sh("mkdir " . $apacheDir . "/ssl", true);
					}

					// Generate a key and self-signed cert
					$publicIp = pake_sh("curl -s http://ipecho.net/plain");
					$helper->status($helper::THREE, "Your public IP Address is: " . $publicIp, 'INFO');
					pake_superuser_sh("openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout " . $apacheDir . "/ssl/" . $hackstackAppName . ".key -out " . $apacheDir . "/ssl/" . $hackstackAppName . ".crt", true);
					
					$configSearchTerms = Array();
					$configReplacements = Array();

					// Replace the .pem and .crt location references
					if(!$defaultLocation) {
						$configSearchTerms[] = "/etc/apache2";
						$configReplacements[] = $apacheDir;
					}

					// Replace 'hackstack' in the apache config if it's not the configured app name
					if($hackstackAppName != "hackstack") {
						$configSearchTerms[] = "hackstack";
						$configReplacements[] = $hackstackAppName;
					}

					// Perform any apache config replacements that need to occur
					if(!empty($configSearchTerms)) {
						file_put_contents(__DIR__ . "/configuration/scripts/apache/hackstack-ssl.conf", str_replace($configSearchTerms, $configReplacements, file_get_contents(__DIR__ . "/configuration/scripts/apache/hackstack-ssl.conf")));
					}
					
					pake_superuser_sh("a2enmod ssl", true);
					pake_superuser_sh("cp " . __DIR__ . "/configuration/scripts/apache/hackstack-ssl.conf " . $sitesLocation . "/" . $hackstackAppName . "-ssl", true);
					pake_superuser_sh("cp " . __DIR__ . "/configuration/scripts/apache/hackstack-redirect.conf " . $sitesLocation . "/" . $hackstackAppName . "-redirect", true);
					pake_sh("cd " . $sitesLocation);
					pake_superuser_sh("a2ensite " . $hackstackAppName . "-redirect", true);
					pake_superuser_sh("a2ensite " . $hackstackAppName . "-ssl", true);
					pake_superuser_sh($apacheService . " restart", true);
					$helper->status($helper::THREE, "Setup an apache virtual host with self-signed cert");
					$helper->status($helper::FOUR, "You need to enable your apache config to listen on port 443.", 'INFO');
					$helper->status($helper::FOUR, "Add these two lines to your ports.conf or httpd.conf in " . $apacheDir, 'INFO');
					$helper->status($helper::FIVE, "NameVirtualHost *:443", 'INFO');
					$helper->status($helper::FIVE, "Listen 443", 'INFO');
				}

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