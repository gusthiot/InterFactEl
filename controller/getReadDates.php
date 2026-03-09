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

    $mp = new State($dir);
    $choices = [];
    $mpNb = 0;
    $version = Version::load('../');

    foreach(globReverse($dir) as $dirYear) {
        $year = basename($dirYear);
        foreach(globReverse($dirYear) as $dirMonth) {
            $month = basename($dirMonth);
            if(Lock::exists($dirMonth, 'month')) {
                foreach(globReverse($dirMonth) as $dirVersion) {
                    foreach(globReverse($dirVersion) as $dirRun) {
                        $infos = Info::load($dirRun);
                        $factel = $infos["FactEl"][2];
                        $vmin = $version["vl-min-relire"][2];
                        if(floatval($factel) >= floatval($vmin)) {
                            if(file_exists($dirMonth."/".ParamZip::NAME)) {
                                $label = Label::load($dirMonth);
                                if(empty($label)) {
                                    $label = "No label ?";
                                }
                            }
                            else {
                                $label = "<i>Idem mois précédent</i>";
                            }
                            $choices["read-".$year.$month] = [$month." ".$year, $label, 1, 0, 1, ""];
                            break;
                        }
                    }
                }

            }
            else {
                if($mp->isSame($month, $year)) {
                    if(Lock::exists($dirMonth."/0", 'version')) {
                        foreach(globReverse($dirMonth) as $dirVersion) {
                            if(Lock::exists($dirVersion, 'version')) {
                                foreach(globReverse($dirVersion) as $dirRun) {
                                    $infos = Info::load($dirRun);
                                    $factel = $infos["FactEl"][2];
                                    $vmin = $version["vl-min-relire"][2];
                                    if(floatval($factel) >= floatval($vmin)) {
                                        if(file_exists($dirMonth."/".ParamZip::NAME)) {
                                            $label = Label::load($dirMonth);
                                            if(empty($label)) {
                                                $label = "No label ?";
                                            }
                                        }
                                        else {
                                            $label = "<i>Idem mois précédent</i>";
                                        }
                                        $choices["read-".$year.$month] = [$month." ".$year, $label, 1, 0, 1, ""];
                                        break;
                                    }
                                    else {
                                        $choices["read-".$year.$month] = [$month." ".$year, "", 0, 0, 1, ""];
                                    }
                                }
                            }
                            else {
                                $choices["read-".$year.$month] = [$month." ".$year, "", 0, 0, 1, $messages->getMessage('msg10')];
                            }
                        }
                    }
                    else {
                        $choices["read-".$year.$month] = [$month." ".$year, "", 0, 0, 0, ""];
                    }
                    $mpNb = count($choices)-1;
                }
                if(Unused::exists($dirMonth)) {
                    $diode = 1;
                    $label = Label::load($dirMonth);
                    if(empty($label)) {
                        $label = "No label ?";
                    }
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
            }
        }
    }
    echo json_encode([$choices, $mpNb]);
}
