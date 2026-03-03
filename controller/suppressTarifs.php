<?php

require_once("../assets/Unused.php");
require_once("../includes/Tarifs.php");
require_once("../session.inc");

/**
 * Called to suppress month tarifs
 */
if(isset($_GET["plate"]) && isset($_GET["date"])) {

    $plateforme = $_GET["plate"];
    checkPlateforme("tarifs", $plateforme);
    $month = substr($_GET["date"], 4, 2);
    $year = substr($_GET["date"], 0, 4);
    $dirTarifs = DATA.$plateforme."/".$year."/".$month."/";
    Unused::remove($dirTarifs);
    Tarifs::suppress($dirTarifs);
    $_SESSION['alert-success'] = "tarifs correctement effacées";
    header('Location: ../tarifs.php?plateforme='.$plateforme);
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
