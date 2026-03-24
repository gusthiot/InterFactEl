<?php

require_once("../assets/Unused.php");
require_once("../assets/Version.php");
require_once("../assets/Lock.php");
require_once("../assets/Message.php");
require_once("../includes/State.php");
require_once("../includes/Tarifs.php");
require_once("../session.inc");

function remove(&$choices, $year, $month, $dirMonth, $idem=true)
{
    $messages = new Message();
    $unused = Unused::load($dirMonth);
    $version = Version::load('../');
    $vmin = $version["vi-min-controler"][2];
    $warning = "";
    if(floatval($unused) < floatval($vmin)) {
        $warning = $messages->getMessage('msg9');
    }
    $choices["remove-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth, $idem), 1, 1, 0, $warning];
}

function maxDate($mp, $dir)
{
    foreach(globReverse($dir) as $dirYear) {
        foreach(globReverse($dirYear) as $dirMonth) {
            if(Unused::exists($dirMonth)) {
                return basename($dirYear).basename($dirMonth);
            }
        }
    }
    return $mp['year'].$mp['month'];
}

if(isset($_POST["plate"])) {


    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = State::firstOpenMonth($dir);
    $choices = [];

    if(!empty($mp['month'])) {
        $messages = new Message();
        $date = maxDate($mp, $dir);

        while(true) {

            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);

            $dirMonth = $dir."/".$year."/".$month;
            if(($date == "202407") || Lock::exists($dirMonth, 'month')) {
                break;
            }

            if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                if(Unused::exists($dirMonth)) {
                    if(Tarifs::v0_exists($dirMonth)) {
                        $dirVersion = globReverse($dirMonth)[0];
                        if(Lock::exists($dirVersion, 'version')) {
                            remove($choices, $year, $month, $dirMonth);
                        }
                        else {
                            $choices["remove-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), 0, 1, 0, $messages->getMessage('msg10')];
                        }
                    }
                    else {
                        remove($choices, $year, $month, $dirMonth, false);
                    }
                }
                else {
                    $choices["remove-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), 0, 0, 0, ""];
                }
            }
            else {
                if(Unused::exists($dirMonth)) {
                    remove($choices, $year, $month, $dirMonth, false);
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
    }
    echo json_encode($choices);

}
