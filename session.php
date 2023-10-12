<?php

require_once("src/Superviseur.php");
require_once("src/Gestionnaire.php");
require_once("src/Message.php");
//require_once("tequila-php-client/tequila.php");
require_once("commons/tequila.php");
require_once("config.php");

$oClient = new TequilaClient();//'https://tequila.epfl.ch', 86400, "InterFactEl");
$oClient->Authenticate();
$_SESSION['user'] = $oClient->getValue('user');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
$messages = new Message();
//$_SESSION['user'] = "gusthiot";