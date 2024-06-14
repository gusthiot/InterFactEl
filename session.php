<?php

require_once("assets/Superviseur.php");
require_once("assets/Gestionnaire.php");
require_once("commons/Tequila.php");
require_once("config.php");

ini_set('display_errors', DISPLAY_ERRORS);
error_reporting(ERROR_REPORTING);

session_start();
$oClient = new TequilaClient('https://tequila.epfl.ch', 86400, "InterFactEl", "", TequilaClient::LANGUAGE_FRENCH);
$oClient->authenticate(['uniqueid'], "", 'group=cmi-fact');
//$oClient->logout();

$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
//$_SESSION['user'] = "gusthiot";

$dataGest = $gestionnaire->getGestionnaire($_SESSION['user']);
if($dataGest) {
    $sciper = $gestionnaire->getGestionnaire($_SESSION['user'])['sciper'];
}
else {
    $_SESSION['alert-info'] = "Vous n'avez aucun droit de gestion";
}
