<?php

require_once("../assets/Sap.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Label.php");
require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../includes/State.php");
require_once("../session.inc");

if(isset($_POST["plate"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = State::lastRun($dir);
    $choices = [];
    $mpNb = 0;

    foreach(globReverse($dir) as $dirYear) {
        $year = basename($dirYear);
        foreach(globReverse($dirYear) as $dirMonth) {
            $month = basename($dirMonth);
            if (file_exists($dirMonth."/".ParamZip::NAME) && (file_exists($dirMonth."/unused.csv"))) {
                if(State::isLaterThan($month, $year, $mp['month'], $mp['year']) || State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                    $label = Label::load($dirMonth);
                    if(empty($label)) {
                        $label = "No label ?";
                    }
                    $choices["control-".$year.$month] = [$month." ".$year, $label];
                    if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                        $mpNb = count($choices)-1;
                    }
                }
            }
            $lastRun = 0;
            $lastVersion = 0;
            foreach(globReverse($dirMonth) as $dirVersion) {
                foreach(globReverse($dirVersion) as $dirRun) {
                    $sap = new Sap($dirRun);
                    $infos = Info::load($dirRun);
                    $factel = $infos["FactEl"][2];
                    if((floatval($factel) > 11.02) && (file_exists($dirRun."/lock.csv") || $sap->status() > 1)) {
                        if(file_exists($dirMonth."/".ParamZip::NAME)) {
                            $label = Label::load($dirMonth);
                            if(empty($label)) {
                                $label = "No label ?";
                            }
                        }
                        else {
                            $label = "<i>Idem mois précédent</i>";
                        }
                        $choices["read-".$year.$month] = [$month." ".$year, $label];
                        if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                            $mpNb = count($choices)-1;
                        }
                        break;
                    }
                }
            }
        }
    }
    echo json_encode([$choices, $mpNb]);
}
