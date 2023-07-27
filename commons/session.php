<?php

require_once("from_csv/Superviseur.php");
require_once("from_csv/Gestionnaire.php");

if ( ! session_id() ) @ session_start();
$_SESSION['sciper'] = "138027";
if(!isset($_SESSION["sciper"])){
    header("Location: login.php");
    exit;
}

$isAllowed = TRUE;
$superviseur = new Superviseur();
$gestionnaire = new Gestionnaire();
$login = $_SESSION['sciper'];