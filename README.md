# HackStack - Don't start from scratch

HackStack is a baseline for a restful PHP website which allows you to focus on building out your idea instead of reimplementing common components.


While built with hackathons and MVPs in mind, HackStack uses production ready components and is designed to offer the best development and user experience.

## Features
Baked in:
* RESTful routes
* Twig templating with automatic caching for both pages and emails
* Logging with separate access and error logs
* Pre-configured templates and routing for:
    * 500, 404, and 403 HTTP errors
    * User authentication including built in support for
       * Sign-up
       * Sign-in
       * Sign-out
       * Forgot password with reset through email
       * Sentry provides failed login throttling and other features natively 
* Cross-browser styling and consistency using Bootstrap

## System Dependencies
* Apache
* MySQL
* [Composer](https://getcomposer.org/)

## Inital Setup
1. Clone the repository to your server into `/var/www/`
2. Change ownership to the apache user and group (or www-data, depending on your OS)
3. Run a `composer install` at the root
4. Update the `configuration/databases.yml` with the parameters needed to connect to your MySQL DB
5. Use pake to run the setup task by executing `./vendor/bin/pake setup` in the root directory
  * This will perform the following tasks:
    1. Set up the database and run the Sentry build script to create the Sentry tables for user authentication and authorization
    2. Create inital log files with symlinks to them called `access` and `error` in the logs directory
    3. Add an apache virtual host and set it up with a self-signed SSL certificate
6. Update the `configuration/mailer.yml` for your mailer. MailGun is a really easy one to get up and running with and works in place
  * A typical Mailgun setup would look like: 
```yaml
development:
    host: smtp.mailgun.org
    port: 465
    username: <Mailgun username>
    password: <Mailgun password>
    sender:
        name: <Name of sending user>
        email: <Mailgun send from Address>
```

## Using Pake
Pake provides an easy way to build and run tasks. To make using pake easier, you can copy the `vendor/bin/pake` file to `/usr/bin` so that you can use the `pake` command directly. To setup new tasks, just define a function in the pakefile. To create a 'clean' task:
```php
pake_task("clean");
pake_desc("Run the clean task");
function run_clean() {
   // Your code for the clean task goes here
}
```

## Components
HackStack is built on the shoulders of giants and uses a number of libraries:

* [Composer](https://github.com/composer/composer) for managing dependencies
* [Slim](https://github.com/composer/composer) as a lightweight routing engine and template renderer
* [Slim-Skeleton](https://github.com/codeguy/Slim-Skeleton) for common Slim components and setup, which includes:
    * [Monolog](https://github.com/Seldaek/monolog) for logging
    * [Twig](https://github.com/fabpot/Twig) for templating
    * [Slim-Views](https://github.com/codeguy/Slim-Views) for rendering Twig templates
* [Slim-Extras](https://github.com/codeguy/Slim-Extras) for some basic Slim middleware, including:
    * [CSRFGuard](https://github.com/codeguy/Slim-Extras/blob/master/Middleware/CsrfGuard.php) for handling and checking CSRF tokens
* [SwiftMailer](https://github.com/swiftmailer/swiftmailer) for handling email delivery
* [Bootstrap](https://github.com/twbs/bootstrap) for clean templating and styling
* [JQuery](https://github.com/jquery/jquery) for bootstrap interactions

Additionally, the following services and other attributions have contributed:

* [Bootstrap CDN](http://www.bootstrapcdn.com) for CDN sources of bootstrap styles
* [CDNJS](http://cdnjs.com/) for CDN sources of bootstrap javascript and jquery
* [Github](http://www.github.com) for the structure of a number of elements including the sign in element
* [Simple Line Icon Webfont](http://graphicburger.com/simple-line-icons-webfont/) for awesome clean icons


## How to Contribute

### Pull Requests

1. Fork the HackStack PHP repository
2. Create a new branch for your changes
    * For new features, your branch name should be prefaced with 'feature-'
    * For bug fixes & improvements, your branch name should be prefaced with 'fix-'
3. Send a pull request from each feature branch to the **development** branch

It is very important to separate new features or improvements into separate feature branches, and to send a
pull request for each branch. This allows us to review and pull in new features or improvements individually.

### Style Guide

You should try to match the styling of the existing code as much as possible.
