<?php
/**
 * Defines root or core routes for error handling and authentication
 */

	$app->get('/', function() use ($app) {
		// Render index view
		$app->render('index.twig');
	});

	$app->get('/signin', function() use ($app) {
		// Render index view
		$app->render('signin.twig');
	});

	$app->get('/signout', function() use ($app) {
		// Authenticate signed in
		// 	Yes; log them out, redirect to homepage with flash
		// 	No; redirect to sign in with flash
		$app->flash("error", "You are not currently signed in");
		$app->redirect("/signin");
	});

	$app->get('/403', function() use ($app) {
		$app->render("403.twig");
	});

	$app->get('/404', function() use ($app) {
		$app->flashNow("error", "Sorry, it looks like the resource you requested doesnt exist.");
		$app->render("404.twig");
	});

	$app->get('/500', function() use ($app) {
		$app->flashNow("error", "Sorry, it looks like something has gone wrong.");
		$app->render("500.twig");
	});
