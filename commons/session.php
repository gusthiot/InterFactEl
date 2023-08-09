<?php

require_once("from_csv/Superviseur.php");
require_once("from_csv/Gestionnaire.php");
require_once("tequila.php");

//if ( ! session_id() ) @ session_start();
//$_SESSION['sciper'] = "138027";
$oClient = new TequilaClient();
$oClient->Authenticate();
$login = $oClient->getValue('user');

$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
//$login = "138027";//$_SESSION['sciper'];