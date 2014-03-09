# Application Logs

Hackstack-php uses [Monolog](https://github.com/Seldaek/monolog) for logging. Logs currently get written to two seperate files, a general log and an error log.

Log name format for the two types will be:

	{4 digit Year}-{3 character Month name}-{2 digit day number}-{error|all}.log

# Using Monolog

To log messages use the various log level functions: 

```php
// Log an error
$app->log->error("An error occurred");

// Log a debug message with some extra data
$app->log->debug("Some debug message", Array(
	"data" => $some_variable_or_value
));

>  This file should be left in place to preserve the directory for git.
