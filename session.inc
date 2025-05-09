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
 * Managing Tequila session
 */

session_start();
$oClient = new TequilaClient(TEQUILA_URL, TEQUILA_TIMEOUT, APP_NAME, APP_URL, TEQUILA_LANGUAGE);
if(DEV_MODE) {
    define( 'USER', DEV_MODE );
}
else {
    $oClient->authenticate(TEQUILA_REQUEST, TEQUILA_ALLOWS, TEQUILA_REQUIRED);
    define( 'USER', $_SESSION[SESSION_USER] );
}


// Authentication Entra
/*
session_start(); 
if (!isset($_SESSION['user']))
{
    header("Location: ".WEBSITE_URL.$_ENV['OIDC_REDIRECT_URI']);
    exit();
}*/
/*
// init user details
define('USER_EMAIL', $_SESSION["user"]->email);
define('USER_SCIPER', $_SESSION["claims"]->uniqueid);
define('USER_FULL_NAME',  $_SESSION["user"]->name);
// We'll recreate the string with group names separated by commas and remove "_AppGrpU" suffix for each group (because Entra will return AD Groups corresponding to https://groups.epfl.ch groups, and they doesn't have the same name)
$group_list = array();
foreach ($_SESSION["claims"]->groups as $ad_group_name)
{
   $group_list[] = str_replace("_AppGrpU", "", $ad_group_name);
}
define('USER_GROUPS', implode(",", $group_list));
*/





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
