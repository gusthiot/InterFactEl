<?php

require_once("src/Superviseur.php");
require_once("src/Gestionnaire.php");
require_once("tequila.php");

//$oClient = new TequilaClient();
//$oClient->Authenticate();
//$login = $oClient->getValue('user');

$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
$login = "gusthiot";