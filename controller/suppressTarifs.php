<?php

require_once("../assets/Label.php");
require_once("../assets/Lock.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Unused.php");
require_once("../includes/Tarifs.php");
require_once("../session.inc");

/**
 * Called to suppress month tarifs
 */
if(isset($_POST["plate"]) && isset($_POST["date"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);
    $month = substr($_POST["date"], 4, 2);
    $year = substr($_POST["date"], 0, 4);
    $dirTarifs = DATA.$plateforme."/".$year."/".$month."/";
    Unused::remove($dirTarifs);
    Tarifs::suppress($dirTarifs);
    if(!file_exists($dirTarifs."/newrates.csv")) {
        Label::remove($dirTarifs);
    }
    echo "ok";
}
