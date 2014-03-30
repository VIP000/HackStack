<?php
/**
 * Defines routes for user profile operations
 */

/**
 * View a user profile
 */
$app->get('/profile/:username', function($username) use($app) {
	if(Sentry::check()) {
		$app->render("users/profile.twig");
	} else {
		$app->redirect('/403');
	}
});