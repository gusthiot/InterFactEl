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
    $mp = State::firstOpenMonth($dir);
    $choices = [];


    if(!empty($mp['month'])) {
        $version = Version::load('../');
        $messages = new Message();

        foreach(globReverse($dir) as $dirYear) {
            $maxYear = basename($dirYear);
            foreach(globReverse($dirYear) as $dirMonth) {
                $maxMonth = basename($dirMonth);
                if(Unused::exists($dirMonth)) {
                    break;
                }
            }
            if(Unused::exists($dirMonth)) {
                break;
            }
        }

        $date = $maxYear.$maxMonth;

        while(true) {

            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);

            $dirMonth = $dir."/".$year."/".$month;
            if(Lock::exists($dirMonth, 'month')) {
                break;
            }


            if(Unused::exists($dirMonth)) {
                if(State::isSameAs($month, $year, $mp['month'], $mp['year']) && !Lock::exists(globReverse($dirMonth)[0], 'version')) {
                    $choices["remove-".$year.$month] = [$month." ".$year, "", 0, 1, 0, $messages->getMessage('msg10')];
                }
                else {
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
            }
            else {
                $choices["remove-".$year.$month] = [$month." ".$year, "", 0, 0, 0, ""];
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
