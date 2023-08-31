<?php

require_once("src/Superviseur.php");
require_once("src/Gestionnaire.php");
require_once("src/Message.php");
//require_once("tequila-php-client/tequila.php");
require_once("commons/tequila.php");
require_once("config.php");

$oClient = new TequilaClient();//'https://tequila.epfl.ch', 86400, "InterFactEl");
$oClient->Authenticate();
$login = $oClient->getValue('user');

$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
$messages = new Message();
//$login = "gusthiot";
//$_SESSION['user'] = $login;
