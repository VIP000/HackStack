<?php
/**
 * Defines root or core routes for error handling and authentication
 */

/* ====================================== User Authentication ====================================== */
	/**
	 * Renders the home page
	 */
	$app->get('/', function() use ($app) {
		// Render index view
		$app->render('index.twig');
	});

	/**
	 * Renders the sign in page
	 */
	$app->get('/signin', function() use ($app) {
		// Render index view
		$app->render('signin.twig');
	});

	/**
	 * If the user is signed in, sign them out, otherwise redirect to 403 page
	 */
	$app->get('/signout', function() use ($app) {
		// Authenticate signed in
		// 	Yes; log them out, redirect to homepage with flash
		// 	No; redirect to sign in with flash
		$app->flash("error", "You are not currently signed in");
		$app->redirect("/signin");
	});

	/**
	 * Renders the forgot password page and accepts a reset request
	 */
	$app->map('/forgot', function() use ($app) {
		// Authenticate signed in
		// 	Yes; redirect to profile
		// 	No; render the forgot password page	
		if($app->request->isGet()) {
			$app->render('forgot_password.twig');
		} else {

		}
	})->via("GET", "POST");

	/**
	 * Receives post parameters and signs in if it matches a valid user
	 */
	$app->post('/authorize', function() use ($app) {
		// Authenticate signed in
			// 	Yes; redirect to user profile
			// 	No; attempt sign in with given validated parameters
				// On success, redirect to internal home
				// On failure, redirect to sign in with failure flash
		$app->flash("error", "It looks like that wasn't quite right. Try again.");
		$app->redirect("/signin");
	});

/* ================================================================================================= */

/* ====================================== Status Codes ====================================== */
	/**
	 * Renders the unauthorized page for HTTP 403 error codes
	 */
	$app->get('/403', function() use ($app) {
		$app->flashNow("error", "You don't have the proper permissions for that.");
		$app->render("403.twig");
	});

	/**
	 * Renders the not found page for HTTP 404 error codes
	 */
	$app->get('/404', function() use ($app) {
		$app->flashNow("error", "Looks like the resource you requested doesnt exist.");
		$app->render("404.twig");
	});

	/**
	 * Renders the server error page for HTTP 500 error codes
	 */
	$app->get('/500', function() use ($app) {
		$app->flashNow("error", "Sorry, it looks like something has gone wrong.");
		$app->render("500.twig");
	});
/* ========================================================================================== */
