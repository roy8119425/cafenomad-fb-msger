<?php
function myErrorHandler( $errType, $errStr, $errFile, $errLine, $errContext )
{
	$displayErrors = ini_get('display_errors');
	$logErrors     = ini_get('log_errors');
	$errorLog      = ini_get('error_log');

	if ($displayErrors) {
		echo $errStr.PHP_EOL;
	}

	if ($logErrors) {
		$message = sprintf('[%s] %s (%s, %s)', date('d-m H:i'), $errStr, $errFile, $errLine);
		file_put_contents($errorLog, $message.PHP_EOL, FILE_APPEND);
	}
}

ini_set('log_errors', 1);
ini_set('error_log', 'debug');

set_error_handler('myErrorHandler');
?>
