<?php

require_once("../assets/Sap.php");
require_once("../assets/Unused.php");
require_once("../assets/Version.php");
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
    $mp = new State($dir);
    $choices = [];
    $version = Version::load('../');

    foreach(globReverse($dir) as $dirYear) {
        $maxYear = basename($dirYear);
        foreach(globReverse($dirYear) as $dirMonth) {
            $maxMonth = basename($dirMonth);
            break;
        }
        break;
    }

    $date = $maxYear.$maxMonth;

    while(true) {

        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);

        $dirMonth = $dir."/".$year."/".$month;
        if(Lock::exists($dirMonth, 'month')) {
            break;
        }


        if($mp->isSame($month, $year)) {
            if(Lock::exists($dirMonth."/0", 'version')) {
                foreach(globReverse($dirMonth) as $dirVersion) {
                    if(Lock::exists($dirVersion, 'version')) {
                        if(Unused::exists($dirMonth)) {
                            $label = Label::load($dirMonth);
                            if(empty($label)) {
                                $label = "No label ?";
                            }
                            $unused = Unused::load($dirMonth);
                            $vmin = $version["vi-min-controler"][2];
                            $warning = "";
                            if(floatval($unused) < floatval($vmin)) {
                                $warning = $messages->getMessage('msg9');
                            }
                            $choices["remove-".$year.$month] = [$month." ".$year, $label, 1, 1, 1, $warning];
                        }
                        else {
                            if(file_exists($dirMonth."/".ParamZip::NAME)) {
                                $label = Label::load($dirMonth);
                                if(empty($label)) {
                                    $label = "No label ?";
                                }
                            }
                            else {
                                $label = "<i>Idem mois précédent</i>";
                            }
                            $choices["remove-".$year.$month] = [$month." ".$year, $label, 0, 0, 1, ""];
                        }
                    }
                    else {
                        $choices["remove-".$year.$month] = [$month." ".$year, "", 0, 0, 1, $messages->getMessage('msg10')];
                    }
                }
            }
            else {
                $choices["remove-".$year.$month] = [$month." ".$year, "", 0, 0, 0, ""];
            }
        }
        else {
            if(Unused::exists($dirMonth)) {
                $label = Label::load($dirMonth);
                if(empty($label)) {
                    $label = "No label ?";
                }
                $unused = Unused::load($dirMonth);
                $vmin = $version["vi-min-controler"][2];
                $warning = "";
                if(floatval($unused) < floatval($vmin)) {
                    $warning = $messages->getMessage('msg9');
                }
                $choices["remove-".$year.$month] = [$month." ".$year, $label, 1, 1, 0, $warning];
            }
            else {
                $choices["remove-".$year.$month] = [$month." ".$year, "", 0, 0, 0, ""];
            }

        }

        if($month == "01") {
            $date -= 89;
        }
        else {
            $date--;
        }
    }

    echo json_encode($choices);
}
