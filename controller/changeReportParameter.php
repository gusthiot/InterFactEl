<?php

require_once("../session.inc");

if(isset($_POST["type"]) && isset($_POST["value"]) && isset($_POST["plate"])) {
    $plateforme = $_POST["plate"];
    checkPlateforme("reporting", $plateforme);
    if(in_array($_POST["type"], ["encoding", "separator"])) {
        $_SESSION[$_POST["type"]] = $_POST["value"];
    }
    
}
