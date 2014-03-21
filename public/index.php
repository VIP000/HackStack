<?php

/* ====================================== Session ====================================== */
	/**
	 * Start the PHP session
	 */
	session_cache_limiter(false);
	session_start();
/* ===================================================================================== */

/* ====================================== App Globals & Autoload ====================================== */
	$ApplicationRoot = dirname(__DIR__);
	require($ApplicationRoot . "/vendor/autoload.php");
	use Illuminate\Database\Capsule\Manager as Capsule;
/* ==================================================================================================== */

/* ====================================== Sentry Setup ====================================== */
// Alias Sentry to make it easier to work with
class_alias('Cartalyst\Sentry\Facades\Native\Sentry', 'Sentry');

// Initialize the DB
\Hackstack\Helpers\DatabaseHelper::getInstance();

// Setup Sentry DB resolver
Sentry::setupDatabaseResolver(Capsule::connection()->getPdo());
/* ==================================================================================================== */

/* ====================================== Application Setup ====================================== */
	/**
	 * Setup and prepare our app with the templates directory
	 */
	$app = new \Slim\Slim(
		array(
			'debug' => true,
			'templates.path' => $ApplicationRoot . "/templates"
		)
	);
/* =============================================================================================== */

/* ====================================== Default Event Handlers ====================================== */
	/**
	 * Setup the handler for Error events
	 */
	$app->error(function(\Exception $e) use ($app) {
		$app->log->error("[EXCEPTION] " . $e->getMessage(), Array(
			"request" => print_r($app->request, true),
			"exception" => print_r($e, true)
		));
		$app->redirect("/500");
	});

	/**
	 * Setup the handler for Not Found events
	 */
	$app->notFound(function() use ($app) {
		$app->log->error("[404 Error] Resource not found", Array(
			"request_uri" => $app->request->getResourceUri()
		));
		$app->redirect("/404");
	});
/* =============================================================================================== */

/* ====================================== Logging ====================================== */
	/**
	 * Create monolog logger and store logger in container as singleton 
	 * (Singleton resources retrieve the same log resource definition each time)
	 */
	$app->container->singleton('log', function() use ($ApplicationRoot) {
		$log = new \Monolog\Logger('application');
		$currentDate = new DateTime();
		$logNameDate = $currentDate->format("Ymd");
		
		/**
		 * Add a processor for the format of log lines
		 */
		$lineFormatter = new \Monolog\Formatter\LineFormatter(
			"[%datetime%] %level_name% : %message%\n\tcontext: %context%\n\textra data: %extra%\n",
			null,
			true
		);

		/**
		 * Setup a general application log using the the line formatter
		 */
		$accessStreamHandler = new \Monolog\Handler\StreamHandler(
			$ApplicationRoot . "/logs/" . $logNameDate ."-access.log", 
			\Monolog\Logger::DEBUG
		);
		$accessStreamHandler->setFormatter($lineFormatter);
		$log->pushHandler($accessStreamHandler);

		/**
		 * Setup the separate error log using the the line formatter
		 */
		$errorStreamHandler = new \Monolog\Handler\StreamHandler(
			$ApplicationRoot . "/logs/" . $logNameDate ."-error.log",
			\Monolog\Logger::ERROR
		);
		$errorStreamHandler->setFormatter($lineFormatter);
		$log->pushHandler($errorStreamHandler);
		
		return $log;
	});
/* ===================================================================================== */

/* ====================================== Templates ====================================== */
	/**
	 * Setup app to use Twig templates for views and configure some options
	 */
	$app->view(new \Slim\Views\Twig());
	$app->view->parserOptions = array(
		'charset' => 'utf-8',
		'cache' => realpath($ApplicationRoot . "/templates/cache"),
		'auto_reload' => true,
		'strict_variables' => false,
		'autoescape' => true
	);
	$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());
/* ======================================================================================= */

/* ====================================== Routes File Loading ====================================== */
	/**
	 * Log the incoming URL request
	 */
	$app->log->debug("Requested resource URI is {" . $app->request->getResourceUri() . "}");

	/**
	 * Loads all *.routes.php files in the routes folder by default as it's safer than dynamically loading
	 * 
	 * NOTE: URIs that do not match a route will automatically be routed to the notFound handler and redirected to /404
	 */	
	$routesCollections = glob($ApplicationRoot . "/routes/*.routes.php");
	if($routesCollections !== false) {
		foreach($routesCollections as $routesCollection) {
			$app->log->debug("Loading the Routes collection at {" . $routesCollection . "}");
			require($routesCollection);
		}
	} else {
		$app->log->error("An error was encountered finding the collection of routes files to load");
	}
/* ================================================================================================= */

/**
 * Run the slim app
 */
$app->run();