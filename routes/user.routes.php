<?php
/**
 * Defines routes for user profile operations
 */

/**
 * If the user is signed in, sign them out, otherwise redirect to 403 page
 */
$app->get('/profile/:username', function($username) {
	if(Sentry::check()) {
		$app->render("users/profile.twig");
	}
});