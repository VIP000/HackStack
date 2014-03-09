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
		$logNameDate = $currentDate->format("Y-M-d");
		
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

/* ====================================== Request URL Parsing ====================================== */
	/**
	 * Split out components of the request URL
	 */
	$RequestUrl = $app->request->getResourceUri();
		$app->log->debug("RequestUrl is {" . $RequestUrl . "}");
	$RequestUrlParts = explode("/", $RequestUrl);
	$BaseUrl = ((count($RequestUrlParts) > 1) ? $RequestUrlParts[1] : "");
		$app->log->debug("BaseUrl is {" . $BaseUrl . "}", Array("RequestUrlParts" => $RequestUrlParts));
/* ================================================================================================= */

/* ====================================== Routes File Loading ====================================== */
	
	/**
	 * Always load the root resource routes file
	 */	
	require($ApplicationRoot . "/routes/root.routes.php");

	/**
	 * Autoload non-root routes files depending on the requested resource
	 * NOTE: URIs that do not match a route will automatically be routed to the notFound handler and redirected to /404
	 */
	if(!empty($BaseUrl)) {
		if(file_exists($ApplicationRoot . "/routes/" . $BaseUrl . ".routes.php")) {
			$app->log->debug("Loading the " . $BaseUrl . ".routes file");
			require($ApplicationRoot . "/routes/" . $BaseUrl . ".routes.php");
		}
	}
/* ================================================================================================= */

/**
 * Run the slim app
 */
$app->run();

?>