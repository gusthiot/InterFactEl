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

if(isset($_POST["plate"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = State::firstOpenMonth($dir);
    $choices = [];
    $version = Version::load('../');

    if(!empty($mp['month'])) {

        if(intval($mp['month']) > 6) {
            $maxYear = State::addToString($mp['year'], 2);
            $maxMonth = State::addToMonth($mp['month'], -6);
        }
        else {
            $maxYear = State::addToString($mp['year'], 1);
            $maxMonth = State::addToMonth($mp['month'], 6);
        }

        $date = $maxYear.$maxMonth;

        while(true) {

            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);

            $dirMonth = $dir."/".$year."/".$month;
            if(Lock::exists($dirMonth, 'month') || ($date == "202407")) {
                break;
            }

            if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                $status = Tarifs::status($dirMonth);
                in_array($status, [8, 9, 10, 11]) ? $warning = $messages->getMessage('msg10') :
                    ($status == 1 ? $warning = Tarif::warning9($dirMonth, $version) : $warning = "");
                $status > 9 ? $base = 1 : $base = 0;
                Unused::exists($dirMonth) ? $diode = 1 : $diode = 0;
                in_array($status, [8, 9, 10, 11]) ? $clic = 0 : $clic = 1;
                in_array($status, [12, 14]) ? $type = "correct" :
                    (in_array($status, [1, 13, 15]) ? $type = "replace" : $type = "load");

                $choices[$type."-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), $clic, $diode, $base, $warning];
            }
            else {
                $label = Tarifs::label($dirMonth);
                if(Unused::exists($dirMonth)) {
                    $choices["replace-".$year.$month] = [$month." ".$year, $label, 1, 1, 0, Tarifs::warning9($dirMonth, $version)];
                }
                else {
                    $choices["load-".$year.$month] = [$month." ".$year, $label, 1, 0, 0, ""];
                }
            }

            if($month == "01") {
                $date -= 89;
            }
            else {
                $date--;
            }
        }
    }
    echo json_encode($choices);
}
