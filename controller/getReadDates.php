<?php

require_once("../assets/Unused.php");
require_once("../assets/Version.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Label.php");
require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../assets/Message.php");
require_once("../includes/State.php");
require_once("../includes/Tarifs.php");
require_once("../session.inc");

/**
 * Called to obtain dates from which we could read/control tarifs
 */
if(isset($_POST["plate"]) && isset($_POST["m0"]) && isset($_POST["status"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $choices = [];
    $m0 = $_POST["m0"];
    $date = Tarifs::maxDate($dir, $m0);
    $m0Nb = 0;

    $version = Version::load('../');
    $messages = new Message();
    $first = Tarifs::firstDate($dir);

    while($date >= $first) {

        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);

        $dirMonth = $dir."/".$year."/".$month;
        $label = Tarifs::label($dirMonth);

        if(Lock::exists($dirMonth, 'month')) {
            foreach(globReverse($dirMonth) as $dirVersion) {
                foreach(globReverse($dirVersion) as $dirRun) {
                    $infos = Info::load($dirRun);
                    $factel = $infos["FactEl"][2];
                    $vmin = $version["vl-min-relire"][2];
                    if(floatval($factel) >= floatval($vmin)) {
                        $choices["read-".$year.$month] = [$month." ".$year, $label, 1, 0, 1, ""];
                        break;
                    }
                }
            }
        }
        else {
            if(Tarifs::v0_exists($dirMonth)) {
                if($m0 == $year.$month) {
                    $status = $_POST["status"];
                    if(Unused::exists($dirMonth)) {
                        $choices["control-".$year.$month] = [$month." ".$year, $label, 1, 1, 0, ""];
                    }
                    $status > 1 ? $base = 1 : $base = 0;
                    if($status > 3) {
                        $clic = 1;
                        $warning = "";
                    }
                    else {
                        $clic = 0;
                        $warning = $messages->getMessage('msg10');
                    }
                    $choices["read-".$year.$month] = [$month." ".$year, $label, $clic, 0, $base, $warning];
                    $m0Nb = count($choices)-1;
                }
                else {
                    $choices["read-".$year.$month] = [$month." ".$year, $label, 0, 0, 1, $messages->getMessage('msg10')];
                }
            }
            else {
                if($year.$month >= $m0) {
                    if(Unused::exists($dirMonth)) {
                        $warning = Tarifs::warning9($dirMonth, $version);
                        empty($warning) ? $clic = 1 : $clic = 0;
                        $choices["control-".$year.$month] = [$month." ".$year, $label, $clic, 1, 0, $warning];
                    }
                    else {
                        $choices["control-".$year.$month] = [$month." ".$year, $label, 0, 0, 0, 0];
                    }
                }
            }
        }
        $date = State::decreaseDate($date);
    }
    echo json_encode([$choices, $m0Nb]);
}
