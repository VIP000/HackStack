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
		$failed = false;
		$errorMessage = "Sorry, something went wrong. Please try again.";

		if($app->request->isGet()) {
			// Render sign up page
			$app->render('signup.twig');
		} else {
			try {
				$parameters = Array(
					// Default to not requiring the user to be authorized manually
					'activated' => true
				);

				// Check that no punctuation exists but allow unicode characters
				// preg_filter will return null if there are no matches, which is what we want to verify so that the value we use matches the one entered by the user
				$filterCheckUsername = preg_filter('/[\pP+]/u', '', trim($app->request->post("username")));
				if(empty($filterCheckUsername)) {
					$username = filter_var(trim($app->request->post("username")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
					if(!empty($username)) {
						$parameters["username"] = $username;
					} else {
						$errorMessage = "You must choose a Username to signup.";
						$failed = true;
					}
				} else {
					$errorMessage = "Sorry, Username cannot contain any punctuation characters.";
					$failed = true;
				}

				// Filter email using the the default filter
				if(!$failed) {
					$trimmedEmail = trim($app->request->post("email"));
					if(!empty($trimmedEmail)) {
						$filteredEmail = filter_var($trimmedEmail, FILTER_SANITIZE_EMAIL);
						if(!empty($filteredEmail)) {
							$parameters["email"] = $filteredEmail;
						} else {
							$errorMessage = "Sorry, that email is not valid or contains invalid characters.";
							$failed = true;
						}
					} else {
						$errorMessage = "You must provide an Email Address to signup.";
						$failed = true;
					}
				}

				// Filter password, allowing letters, numbers, and punctuation
				if(!$failed) {
					$trimmedPassword = trim($app->request->post('password'));
					if(!empty($trimmedPassword)) {
						$filteredPassword = filter_var($trimmedPassword, FILTER_SANITIZE_URL);
						if(!empty($filteredPassword)) {
							$parameters["password"] = $filteredPassword;
						} else {
							$errorMessage = "Sorry, your password must only consist of letters, numbers, and punctuation.";
							$failed = true;
						}
					} else {
						$errorMessage = "You must choose a Password to signup.";
						$failed = true;
					}
				}

				// Check that no punctuation except dashes exists, but allow unicode characters
				// preg_filter will return null if there are no matches, which is what we want to verify so that the value we use matches the one entered by the user
				if(!$failed) {
					$filterCheckFirstName = preg_filter('/[^\P{P}-]+/u', '', trim($app->request->post("first_name")));
					if(empty($filterCheckFirstName)) {
						$firstName = filter_var(trim($app->request->post("first_name")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
						if(!empty($firstName)) {
							$parameters["first_name"] = $firstName;
						}
					} else {
						$errorMessage = "Sorry, First Name cannot contain any punctuation characters besides hyphens.";
						$failed = true;
					}
				}

				// Check that no punctuation except dashes exists, but allow unicode characters
				// preg_filter will return null if there are no matches, which is what we want to verify so that the value we use matches the one entered by the user
				if(!$failed) {
					$filterCheckLastName = preg_filter('/[^\P{P}-]+/u', '', trim($app->request->post("last_name")));
					if(empty($filterCheckLastName)) {
						$lastName = filter_var(trim($app->request->post("last_name")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
						if(!empty($lastName)) {
							$parameters["last_name"] = $lastName;
						}
					} else {
						$errorMessage = "Sorry, Last Name cannot contain any punctuation characters besides hyphens.";
						$failed = true;
					}
				}

				if(!$failed) {
					// Create the user
					$user = Sentry::createUser($parameters);

					$view = $app->view();
					$view->setData('user', $user);
					$body = $view->render("emails/welcome.twig");

					$mailer = \Hackstack\Helpers\MailServiceHelper::getInstance();
					$sent = $mailer->message($user["email"], "Hackstack - Thanks for Joining!", $body);

					$app->flash('info', "Welcome <b>" . $user["first_name"] . " " . $user["first_name"] . "</b> to hackstack!");
					$app->redirect("/profile/" . $user["username"]);
				}
			} catch (Cartalyst\Sentry\Users\LoginRequiredException $e) {
				$errorMessage = "You need to provide an email in order to signup.";
				$failed = true;
			} catch (Cartalyst\Sentry\Users\PasswordRequiredException $e) {
				$errorMessage = "A password is required to signup.";
				$failed = true;
			} catch (Cartalyst\Sentry\Users\UserExistsException $e) {
				$errorMessage = "Sorry that email is already taken. Perhaps you already have an account?";
				$failed = true;
			} catch (Cartalyst\Sentry\Groups\GroupNotFoundException $e) {
				$failed = true;
			}
		}

		$app->log->debug("[signup] Failed -> " . $failed);
		$app->log->debug("[signup] Error Message -> " . $errorMessage);

		if($failed) {
			$app->flash("error", $errorMessage);
			$app->redirect("/signup");
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
					'email'    => filter_var($app->request->post('email'), FILTER_SANITIZE_EMAIL),
					'password' => filter_var($app->request->post('password'), FILTER_SANITIZE_URL)
				);

				if($credentials['email'] === false) {
					$errorMessage = "You provided an invalid email address. Please try again.";
					$failed = true;
				} else if($credentials['password'] === false) {
					$errorMessage = "You provided a password with invalid characters or length. Please try again.";
					$failed = true;
				} else {
					// Try to authenticate the user
					$user = Sentry::authenticate($credentials, false);
					if(!empty($user)) {
						$app->redirect("/profile/" . $user["username"]);
					} else {
						$failed = true;
					}
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

		$app->log->debug("[signin] Failed -> " . $failed);
		$app->log->debug("[signin] Error Message -> " . $errorMessage);

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
	 * Renders the forgot password page for GET requests and accepts a reset request for POST requests
	 */
	$app->map('/forgot', function() use ($app) {
		if($app->request->isGet()) {
			$app->render('forgot.twig');
		} else {
			try {
				$requestedEmail = $app->request->post('email');
				if(!empty($requestedEmail)) {
					// Pull the email address being requested
					$email = filter_var($requestedEmail, FILTER_SANITIZE_EMAIL);

					if(!empty($email)) {
						// Find the user for the given email address
						$user = Sentry::findUserByLogin($email);

						// Get the password reset code
						$resetCode = $user->getResetPasswordCode();

						$view = $app->view();
						$view->setData('user', $user);
						$view->setData('reset_url', $app->urlFor('reset', Array('code' => $resetCode)));
						// Render the email body to be sent
						$body = $view->render("emails/reset_password.twig");

						$mailer = \Hackstack\Helpers\MailServiceHelper::getInstance();
						$sent = $mailer->message($email, "Hackstack - Reset your Password", $body);

						if($sent === 1) {
							$app->flash('info', "You should receive a link to reset your password shortly.");
							$app->redirect("/");
						} else {
							$app->flash('error', "Something went wrong with your request. Please try again.");
							$app->redirect("/forgot");
						}
					} else {
						$app->redirect("/");
					}
				} else {
					$app->flash('error', "You need to provide your email to reset your password.");
					$app->redirect("/forgot");	
				}
			} catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
				$app->flash('info', "It looks like you don't have an account. How about signing up?");
				$app->redirect("/signup");
			}
		}
	})->via("GET", "POST");

	/**
	 * Renders the forgot password page for GET requests and accepts a reset request for POST requests
	 */
	$app->get('/reset(/:code)', function($code = null) use ($app) {
		if($app->request->isGet()) {
			if(!empty($code)) {
				$app->render('reset.twig', Array(
					'reset_code' => $code
				));
			} else {
				$app->redirect("/403");
			}
		} else {
			$emailAddress = filter_var($app->request->post('email'), FILTER_SANITIZE_EMAIL);
			if(!empty($emailAddress)) {
				try {
					// Find the user
					$user = Sentry::findUserByLogin($emailAddress);

					$passwordOne = filter_var($app->request->post('password_first'), FILTER_SANITIZE_URL);
					$passwordTwo = filter_var($app->request->post('password_second'), FILTER_SANITIZE_URL);

					if(empty($passwordOne)) {
						$app->flash('error', "You need to provide a new password");
						$app->redirect("/reset/" . $app->request->post('reset_code'));
					} else if(empty($passwordTwo)) {
						$app->flash('error', "The two passwords did not match");
						$app->redirect("/reset/" . $app->request->post('reset_code'));
					} else if($passwordOne != $passwordTwo) {
						$app->flash('error', "The two passwords did not match");
						$app->redirect("/reset/" . $app->request->post('reset_code'));
					}

					// Check if the reset password code is valid
					if($user->checkResetPasswordCode($app->request->post('reset_code'))) {
						// Attempt to reset the password
						if($user->attemptResetPassword($app->request->post('reset_code'), $passwordTwo)) {
							$user = Sentry::authenticate(Array(
								"email" => $emailAddress,
								"password" => $passwordTwo
							), false);

							if(!empty($user)) {
								$app->flash('info', "Your password has successfully been reset");
								$app->redirect("/profile/" . $user["username"]);
							} else {
								$app->flash('error', "Sorry, resetting your password failed.");
								$app->redirect("/403");
							}
						} else {
							$app->flash('error', "Sorry, your request failed. Please try again.");
							$app->redirect("/reset/" . $app->request->post('reset_code'));
						}
					} else {
						$app->flash('error', "Sorry, that's an invalid request.");
						$app->redirect("/403");
					}
				} catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
					$app->flash('error', "Sorry, it doesn't look like that was a correct email.");
					$app->redirect("/403");
				}
			} else {
				$app->flash('error', "You need to provide your email address");
				$app->redirect("/reset/" . $app->request->post('reset_code'));
			}
		}
	})->via("GET", "POST")->name("reset");

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
