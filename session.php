<?php

require_once("src/Superviseur.php");
require_once("src/Gestionnaire.php");
require_once("src/Message.php");
require_once("tequila-php-client/tequila.php");
//require_once("commons/tequila.php");
require_once("config.php");

session_start();
$oClient = new TequilaClient('https://tequila.epfl.ch', 86400, "InterFactEl", "", TequilaClient::LANGUAGE_FRENCH);
//$oClient->Authenticate();
$oClient->authenticate(['uniqueid'], "", 'group=cmi-fact');
//$oClient->logout();

$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
$messages = new Message();
//$_SESSION['user'] = "gusthiot";