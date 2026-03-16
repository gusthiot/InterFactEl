<?php

require_once("../assets/Sap.php");
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

    if(!empty($mp['month'])) {
        $version = Version::load('../');
        $messages = new Message();

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
            if(Lock::exists($dirMonth, 'month')) {
                break;
            }


            if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                if(file_exists($dirMonth."/".ParamZip::NAME)) {
                    $label = Label::load($dirMonth);
                    if(empty($label)) {
                        $label = "No label ?";
                    }
                }
                else {
                    $label = "<i>Idem mois précédent</i>";
                }
                if(file_exists($dirMonth."/0")) {
                    foreach(globReverse($dirMonth) as $dirVersion) {
                        if(Lock::exists($dirVersion, 'version')) {
                            if(Unused::exists($dirMonth)) {
                                $unused = Unused::load($dirMonth);
                                $vmin = $version["vi-min-controler"][2];
                                $warning = "";
                                if(floatval($unused) < floatval($vmin)) {
                                    $warning = $messages->getMessage('msg9');
                                }
                                $choices["replace-".$year.$month] = [$month." ".$year, $label, 1, 1, 1, $warning];
                            }
                            else {
                                $choices["correct-".$year.$month] = [$month." ".$year, $label, 1, 0, 1, ""];
                            }
                        }
                        else {
                            $base = 0;
                            if(floatval(basename($dirVersion)) > 0) {
                                $base = 1;
                            }
                            $choices["load-".$year.$month] = [$month." ".$year, $label, 0, 0, $base, $messages->getMessage('msg10')];
                        }
                    }
                }
                else {
                    $choices["load-".$year.$month] = [$month." ".$year, "", 1, 0, 0, ""];
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
                    $choices["replace-".$year.$month] = [$month." ".$year, $label, 1, 1, 0, $warning];
                }
                else {
                    $choices["load-".$year.$month] = [$month." ".$year, "", 1, 0, 0, ""];
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
