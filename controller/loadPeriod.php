<?php

require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called to load data period for testing
 */
if(isset($_GET["from"]) && isset($_GET["to"]) && isset($_GET["plate"])) {
    $plateforme = $_GET["plate"];
    checkPlateforme("facturation", $plateforme);
    if(IS_SUPER && TEST_MODE == "TEST") {  
        exec(sprintf("rm -rf %s", escapeshellarg(DATA.$plateforme)));
        $date = $_GET["from"];
        while(true) {
            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);
            $prod = str_replace("data", "../prod/data", DATA.$plateforme)."/".$year."/".$month;
            $dir = DATA.$plateforme."/".$year."/".$month;
            State::recurseCopy($prod, $dir);

            if($date == $_GET["to"]) {
                break;
            }

            if($month == "12") {
                $date += 89;
            }
            else {
                $date++;
            }
        }
        $_SESSION['alert-success'] = "Période correctement chargée";
    }
    else {
        $_SESSION['alert-danger'] = "wrong place, wrong user";
    }

    header('Location: ../facturation.php?plateforme='.$plateforme);
} 
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}

