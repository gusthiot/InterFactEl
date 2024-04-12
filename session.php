<?php

require_once("src/Superviseur.php");
require_once("src/Gestionnaire.php");
require_once("src/Message.php");
require_once("commons/Tequila.php");
require_once("commons/State.php");
require_once("config.php");

ini_set('display_errors', DISPLAY_ERRORS);
error_reporting(ERROR_REPORTING);

session_start();
$oClient = new TequilaClient('https://tequila.epfl.ch', 86400, "InterFactEl", "", TequilaClient::LANGUAGE_FRENCH);
$oClient->authenticate(['uniqueid'], "", 'group=cmi-fact');
//$oClient->logout();

$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
$state = new State();
$messages = new Message();
//$_SESSION['user'] = "gusthiot";
