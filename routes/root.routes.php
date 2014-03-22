<?php
/**
 * Defines root or core routes for error handling and authentication
 */

/* ====================================== Base ====================================== */
	/**
	 * Renders the home page
	 */
	$app->get('/', function() use ($app) {
		// Render index view
		$app->render('index.twig');
	});
/* ================================================================================== */

/* ====================================== User Authentication ====================================== */
	
	/**
	 * Renders the sign up page for GET requests and accepts user creation parameters for POST
	 */
	$app->map('/signup', function() use ($app) {
		if($app->request->isGet()) {
			// Render sign up page
			$app->render('signup.twig');
		} else {
			try {
				// Create the user
				$user = Sentry::createUser(array(
					'email'     => 'john.doe@example.com',
					'password'  => 'test',
					'activated' => true,
					));

				// Find the group using the group id
				$adminGroup = Sentry::findGroupById(1);

				// Assign the group to the user
				$user->addGroup($adminGroup);
			} catch (Cartalyst\Sentry\Users\LoginRequiredException $e) {
				echo 'Login field is required.';
			} catch (Cartalyst\Sentry\Users\PasswordRequiredException $e) {
				echo 'Password field is required.';
			} catch (Cartalyst\Sentry\Users\UserExistsException $e) {
				echo 'User with this login already exists.';
			} catch (Cartalyst\Sentry\Groups\GroupNotFoundException $e) {
				echo 'Group was not found.';
			}
		}
	})->via('GET', 'POST');	

	/**
	 * Renders the sign in page for GET requests and accepts user login parameters for POST
	 */
	$app->map('/signin', function() use ($app) {
		$failed = false;
		$errorMessage = "It looks like that wasn't quite right. Try again.";

		if($app->request->isGet()) {
			// Check if the user is currently signed in and redirect to their profile if so
			if(Sentry::check()) {
				$user = Sentry::getUser();
				if(!empty($user["username"])) {
					$app->redirect("/profile/" . $user["username"]);
				} else {
					$failed = true;
				}
			} else {
				// Render sign in page
				$app->render('signin.twig');
			}
		} else {
			try {
				// Set login credentials
				$credentials = Array(
					'email'    => filter_var($app->request->post('login'), FILTER_SANITIZE_EMAIL),
					'password' => strip_tags($app->request->post('password'))
				);

				// Try to authenticate the user
				$user = Sentry::authenticate($credentials, false);
				if(!empty($user)) {
					$app->redirect("/profile/" . $user["username"]);
				} else {
					$failed = true;
				}
			} catch (Cartalyst\Sentry\Users\LoginRequiredException $e) {
				$errorMessage = "Email is required to login.";
				$failed = true;
			} catch (Cartalyst\Sentry\Users\PasswordRequiredException $e) {
				$errorMessage = "Password is required to login.";
				$failed = true;
			} catch (Cartalyst\Sentry\Users\WrongPasswordException $e) {
				$errorMessage = "Sorry, that wasn't quite right. Try again.";
				$failed = true;
			} catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
				$errorMessage = "Doesn't look like you are registered. You should sign up first.";
				$failed = true;
			} catch (Cartalyst\Sentry\Users\UserNotActivatedException $e) {
				$errorMessage = "You need to confirm your account before you can log in.";
				$failed = true;
			} catch (Cartalyst\Sentry\Throttling\UserSuspendedException $e) {
				$errorMessage = "Looks like you have tried to login too many times without success. Try again later.";
				$failed = true;
			} catch (Cartalyst\Sentry\Throttling\UserBannedException $e) {
				$errorMessage = "Sorry, your account has been suspended.";
				$failed = true;
			}
		}

		$app->log->debug("Failed -> " . $failed);
		$app->log->debug("Error Message -> " . $errorMessage);

		if($failed) {
			$app->flash("error", $errorMessage);
			$app->redirect("/signin");
		}
	})->via('GET', 'POST');

	/**
	 * If the user is signed in, sign them out, otherwise redirect to 403 page
	 */
	$app->get('/signout', function() use ($app) {
		if(Sentry::check()) {
			$app->flash('info', "You are now signed out");
		}

		// Sentry logout will work even if no user is signed in and should never generate an error or exception
		Sentry::logout();
		$app->redirect("/");		
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
