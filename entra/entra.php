<?php

require_once(__DIR__ . '/../openid/vendor/autoload.php');
use Jumbojett\OpenIDConnectClient;

require_once("../config.inc");

// Read configuration
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();
// Init Entra client
$oidc = new OpenIDConnectClient($_ENV['AUTH_URL'], $_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']);
$oidc->setRedirectURL(WEBSITE_URL.$_ENV['OIDC_REDIRECT_FILE']);
$oidc->setResponseTypes(['code']);
$oidc->authenticate();

// Get user information
$_SESSION['user'] = $oidc->requestUserInfo();
// This will allow you to have 'sciper' (uniqueid) and 'username' (gaspar) information
$_SESSION['claims'] = $oidc->getVerifiedClaims();

// Go back to website
header("Location: ".WEBSITE_URL);
exit;
