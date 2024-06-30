<?php

require_once("config.php");
require_once("assets/Superviseur.php");
require_once("assets/Gestionnaire.php");
require_once("includes/Tequila.php");

ini_set('display_errors', DISPLAY_ERRORS);
error_reporting(ERROR_REPORTING);

session_start();
$oClient = new TequilaClient('https://tequila.epfl.ch', 86400, "InterFactEl", "", TequilaClient::LANGUAGE_FRENCH);
if(DEV_MODE) {
    $user = "gusthiot";
}
else {
    $oClient->authenticate(['uniqueid'], "", 'group=cmi-fact');
    $user = $_SESSION['Tequila-Session-User'];
}
//$oClient->logout();

$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
$dataGest = $gestionnaire->getGestionnaire($user);
if($dataGest) {
    $sciper = $gestionnaire->getGestionnaire($user)['sciper'];
}
else {
    $_SESSION['alert-info'] = "Vous n'avez aucun droit de gestion";
}

function checkGest($dataGest)
{
    if(!$dataGest) {
        header('Location: index.php');
        exit;
    }
}

function checkPlateforme($dataGest, $plateforme)
{
    if(!array_key_exists($plateforme, $dataGest['plates'])) {
        $_SESSION['alert-danger'] = "Ce numéro de plateforme n'est pas pris en compte !";
        header('Location: index.php');
        exit;
    }
}
