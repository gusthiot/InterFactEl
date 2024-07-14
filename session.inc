<?php

require_once("config.inc");
require_once("assets/Superviseur.php");
require_once("assets/Gestionnaire.php");
require_once("includes/Tequila.php");

/**
 * Managing error reporting
 */
ini_set('display_errors', DISPLAY_ERRORS);
error_reporting(ERROR_REPORTING);

/**
 * Managing session
 */
session_start();
$oClient = new TequilaClient(TEQUILA_URL, TEQUILA_TIMEOUT, APP_NAME, APP_URL, TEQUILA_LANGUAGE);
if(DEV_MODE) {
    $user = DEV_MODE;
}
else {
    $oClient->authenticate(TEQUILA_REQUEST, TEQUILA_ALLOWS, TEQUILA_REQUIRED);
    if(TEST_MODE) {
        $user = $_SESSION['Tequila-Session-User-Test'];
    }
    else {
        $user = $_SESSION['Tequila-Session-User'];
    }
}

/**
 * Managing user rights
 */
$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
$dataGest = $gestionnaire->getGestionnaire($user);
if($dataGest) {
    $sciper = $gestionnaire->getGestionnaire($user)['sciper'];
}
else {
    $_SESSION['alert-info'] = "Vous n'avez aucun droit de gestion";
}

/**
 * Checks if user has managing rights
 *
 * @param array $dataGest user managing rights 
 * @return void
 */
function checkGest(array $dataGest): void
{
    if(!$dataGest) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Checks if user has managing rights for a given plateform
 *
 * @param array $dataGest user managing rights
 * @param string $plateforme plateform number
 * @return void
 */
function checkPlateforme(array $dataGest, string $plateforme): void
{
    if(!array_key_exists($plateforme, $dataGest['plates'])) {
        $_SESSION['alert-danger'] = "Ce numéro de plateforme n'est pas pris en compte !";
        header('Location: index.php');
        exit;
    }
}
