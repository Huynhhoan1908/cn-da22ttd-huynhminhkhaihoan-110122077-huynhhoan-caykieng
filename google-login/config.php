<?php
// Load Composer autoloader reliably relative to this file's directory.
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
	// Provide a clear error so developer knows how to fix it.
	http_response_code(500);
	echo "Composer autoload not found at: $autoload\n";
	echo "Run `composer install` in the project root (c:/xampp/htdocs/WebCN) to install dependencies.";
	exit;
}
require_once $autoload;

$client = new Google_Client();
$client->setClientId("902945385310-v80qjnnlfi6k3v8ctt7n8svvthm640be.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/WebCN/google-login/callback.php");
$client->addScope("email");
$client->addScope("profile");
?>
