<?php

require_once("../assets/Unused.php");
require_once("../assets/Version.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Label.php");
require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../assets/Message.php");
require_once("../includes/State.php");
require_once("../session.inc");

if(isset($_POST["plate"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = State::firstOpenMonth($dir);
    $choices = [];
    $mpNb = 0;


    if(!empty($mp['month'])) {
        $version = Version::load('../');
        $messages = new Message();

        foreach(globReverse($dir) as $dirYear) {
            $year = basename($dirYear);
            foreach(globReverse($dirYear) as $dirMonth) {
                $month = basename($dirMonth);
                if(file_exists($dirMonth."/".ParamZip::NAME)) {
                    $label = Label::load($dirMonth);
                    if(empty($label)) {
                        $label = "No label ?";
                    }
                }
                else {
                    if(State::isSameAs($month, $year, $mp['month'], $mp['year']) || State::isLaterThan($mp['month'], $mp['year'], $month, $year)) {
                        $label = "<i>Idem mois précédent</i>";
                    }
                    else {
                        $label = "";
                    }
                }
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
                    if(Unused::exists($dirMonth)) {
                        $diode = 1;
                        $unused = Unused::load($dirMonth);
                        $vmin = $version["vi-min-controler"][2];
                        $warning = "";
                        $click = 1;
                        if(floatval($unused) < floatval($vmin)) {
                            $click = 0;
                            $warning = $messages->getMessage('msg9');
                        }
                        $choices["control-".$year.$month] = [$month." ".$year, $label, $click, $diode, 0, $warning];
                    }
                    if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                        if(file_exists($dirMonth."/0")) {
                            foreach(globReverse($dirMonth) as $dirVersion) {
                                if(Lock::exists($dirVersion, 'version')) {
                                    foreach(globReverse($dirVersion) as $dirRun) {
                                        $infos = Info::load($dirRun);
                                        $factel = $infos["FactEl"][2];
                                        $vmin = $version["vl-min-relire"][2];
                                        if(floatval($factel) >= floatval($vmin)) {
                                            $choices["read-".$year.$month] = [$month." ".$year, $label, 1, 0, 1, ""];
                                            break;
                                        }
                                        else {
                                            $base = 0;
                                            if(floatval(basename($dirVersion)) > 0) {
                                                $base = 1;
                                            }
                                            $choices["read-".$year.$month] = [$month." ".$year, $label, 0, 0, $base, ""];
                                        }
                                    }
                                }
                                else {
                                    $choices["read-".$year.$month] = [$month." ".$year, $label, 0, 0, 1, $messages->getMessage('msg10')];
                                }
                            }
                        }
                        $mpNb = count($choices)-1;
                    }
                }
            }
        }
    }
    echo json_encode([$choices, $mpNb]);
}
