# Application Logs

Hackstack-php uses [Monolog](https://github.com/Seldaek/monolog) for logging. Logs currently get written to two seperate files, a general log and an error log.

Log file name format for the two types will be:

	{4 digit Year}-{3 character Month name}-{2 digit day number}-{error|access}.log

Log entries will consist of the main line for the timestamp, log level, and message. Secondary indented lines will be written for the context and extra data. This format can be adjusted by changing the line formatter setup in the main index file

# Using Monolog

To log messages use the various log level functions: 

```php
// Log an error
$app->log->error("An error occurred");

// Log a debug message with some extra data
$app->log->debug("Some debug message", Array(
	"data" => $some_variable_or_value
));
```

>  This file should be left in place to preserve the directory for git.
