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

if(isset($_POST["plate"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = State::firstOpenMonth($dir);
    $choices = [];
    $mpNb = 0;

    $mpProcessed = false;

    function processMp(&$choices, $mp, $dir)
    {
        $month = $mp['month'];
        $year = $mp['year'];
        $dirMonth = $dir."/".$year."/".$month;
        $status = Tarifs::status($dirMonth);
        if(Unused::exists($dirMonth)) {
            $clic = 1;
            $warning = "";
            if($status == 1) {
                $warning = Tarifs::warning9($dirMonth);
                if(empty($warning)) {
                    $clic = 0;
                }
            }
            $choices["control-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), $clic, 1, 0, $warning];
        }
        if($status == 0) {
            $choices["control-".$year.$month] = [$month." ".$year, "", 0, 0, 0, 0];
        }
        if($status > 7) {
            ($status > 9) ? $base = 1 : $base = 0;
            $warning = "";
            $clic = 1;
            if(in_array($status, [8, 9, 10, 11])) {
                $warning = $messages->getMessage('msg10');
                $clic = 0;
            }
            $choices["read-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), $clic, 0, $base, $warning];
        }
    }


    if(!empty($mp['month'])) {
        $version = Version::load('../');
        $messages = new Message();

        foreach(globReverse($dir) as $dirYear) {
            $year = basename($dirYear);
            foreach(globReverse($dirYear) as $dirMonth) {
                $month = basename($dirMonth);

                if(!$mpProcessed && (intval($year.$month) < intval($mp['year'].$mp['month']))) {
                    processMp($choices, $mp, $dir);
                    $mpProcessed = true;
                }

                if(Lock::exists($dirMonth, 'month')) {
                    foreach(globReverse($dirMonth) as $dirVersion) {
                        foreach(globReverse($dirVersion) as $dirRun) {
                            $infos = Info::load($dirRun);
                            $factel = $infos["FactEl"][2];
                            $vmin = $version["vl-min-relire"][2];
                            if(floatval($factel) >= floatval($vmin)) {
                                $choices["read-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth, true), 1, 0, 1, ""];
                                break;
                            }
                        }
                    }
                }
                else {
                    if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                        processMp($choices, $mp, $dir);
                        $mpProcessed = true;
                        $mpNb = count($choices)-1;
                    }
                    else {
                        if(Unused::exists($dirMonth)) {
                            $warning = Tarifs::warning9($dirMonth);
                            empty($warning) ? $clic = 0 : $clic = 1;
                            $choices["control-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), $clic, 1, 0, $warning];
                        }
                        else {
                            $choices["control-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), 0, 0, 0, 0];
                        }
                    }
                }
            }
        }
    }
    echo json_encode([$choices, $mpNb]);
}
