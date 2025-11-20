<?php

require_once("config.inc");
require_once("assets/Superviseur.php");
require_once("assets/Gestionnaire.php");

/**
 * Managing error reporting
 */
ini_set('display_errors', DISPLAY_ERRORS);
error_reporting(ERROR_REPORTING);

/**
 * Authentication Entra
 */
session_start(); 
if(DEV_MODE) {
    define( 'USER', DEV_MODE );
}
else {
    if (!isset($_SESSION['user']))
    {
        header("Location: https://cmifact.epfl.ch/entra/entra.php");
        exit();
    }
    define( 'USER', $_SESSION["claims"]->gaspar );
}

if (!isset($_SESSION['encoding'])) {
    $_SESSION['encoding'] = 'Windows-1252';
}

if (!isset($_SESSION['separator'])) {
    $_SESSION['separator'] = 'pv';
}

/**
 * Managing user rights
 */
$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
define( 'DATA_GEST', $gestionnaire->getGestionnaire(USER) );
if(!DATA_GEST) {
    $_SESSION['alert-info'] = "Vous n'avez aucun droit de gestion";
}
define( 'IS_SUPER', $superviseur->isSuperviseur(USER) );

/**
 * Checks if user has managing rights
 *
 * @param string $type type of right 
 * @return void
 */
function checkGest(string $type): void
{
    if(!DATA_GEST[$type]) {
        $_SESSION['alert-danger'] = "Vous n'avez aucun droit de ".$type." !";
        header('Location: index.php');
        exit;
    }
}

/**
 * Checks if user has managing rights for a given plateform
 *
 * @param string $type type of right 
 * @param string $plateforme plateform number
 * @return void
 */
function checkPlateforme(string $type, string $plateforme): void
{
    checkGest($type);
    if(!array_key_exists($plateforme, DATA_GEST[$type])) {
        $_SESSION['alert-danger'] = "Ce numéro de plateforme n'est pas pris en compte !";
        header('Location: index.php');
        exit;
    }
}

/**
 * Gives a directory content in reverse order
 *
 * @param string $dir targeted directory
 * @return array
 */
function globReverse(string $dir): array
{
    return array_reverse(glob($dir."/*", GLOB_ONLYDIR));
}
