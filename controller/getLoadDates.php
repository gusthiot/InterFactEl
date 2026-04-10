<?php

require_once("../assets/Unused.php");
require_once("../assets/Version.php");
require_once("../assets/Label.php");
require_once("../assets/Lock.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Message.php");
require_once("../includes/State.php");
require_once("../includes/Tarifs.php");
require_once("../session.inc");

/**
 * Called to obtain dates on which we could apply new tarifs
 */
if(isset($_POST["plate"]) && isset($_POST["m0"]) && isset($_POST["status"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $m0 = $_POST["m0"];
    $choices = [];

    $version = Version::load('../');
    $messages = new Message();
    $month = substr($m0, 4, 2);
    $year = substr($m0, 0, 4);

    if(intval($month) > 6) {
        $maxYear = State::addToString($year, 2);
        $maxMonth = State::addToMonth($month, -6);
    }
    else {
        $maxYear = State::addToString($year, 1);
        $maxMonth = State::addToMonth($month, 6);
    }

    $date = $maxYear.$maxMonth;
    $first = Tarifs::firstDate($dir);

    while($date >= $first) {

        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);

        $dirMonth = $dir."/".$year."/".$month;
        if(Lock::exists($dirMonth, 'month')) {
            break;
        }

        $label = Tarifs::label($dirMonth);

        if(Tarifs::v0_exists($dirMonth)) {
            if($m0 == $date) {
                $status = $_POST["status"];
                $status > 1 ? $base = 1 : $base = 0;
                if($status > 3) {
                    $clic = 1;
                    $warning = "";
                    $type = "load";
                    }
                    else {
                    $clic = 0;
                    $warning = $messages->getMessage('msg10');
                    Unused::exists($dirMonth) ? $type = "replace" : $type = "correct";
                    }
                Unused::exists($dirMonth) ? $diode = 1 : $diode = 0;
                $choices[$type."-".$year.$month] = [$month." ".$year, $label, $clic, $diode, $base, $warning];
            }
            else {
                $choices["load-".$year.$month] = [$month." ".$year, $label, 0, 0, 1, $messages->getMessage('msg10')];
            }

        }
        else {
            if(Unused::exists($dirMonth)) {
                $choices["replace-".$year.$month] = [$month." ".$year, $label, 1, 1, 0, Tarifs::warning9($dirMonth, $version)];
            }
            else {
                $choices["load-".$year.$month] = [$month." ".$year, $label, 1, 0, 0, ""];
            }
        }

        $date = State::decreaseDate($date);
    }

    echo json_encode($choices);
}
