<?php

require_once("../assets/Unused.php");
require_once("../assets/Version.php");
require_once("../assets/Lock.php");
require_once("../assets/Message.php");
require_once("../includes/State.php");
require_once("../includes/Tarifs.php");
require_once("../session.inc");

function loadOrReplace(&$choices, $year, $month, $dirMonth, $idem=true)
{
    if(Unused::exists($dirMonth)) {
        replace($choices, $year, $month, $dirMonth, $idem);
    }
    else {
        load($choices, $year, $month, $dirMonth, $idem);
    }
}

function fade(&$choices, $year, $month, $dirMonth, $base)
{
    $messages = new Message();
    $choices["load-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), 0, 0, $base, $messages->getMessage('msg10')];
}

function load(&$choices, $year, $month, $dirMonth, $idem)
{
    $choices["load-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth, $idem), 1, 0, 0, ""];
}

function correct(&$choices, $year, $month, $dirMonth)
{
    $choices["correct-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), 1, 0, 1, ""];
}

function replace(&$choices, $year, $month, $dirMonth, $idem, $base=0)
{
    $messages = new Message();
    $unused = Unused::load($dirMonth);
    $version = Version::load('../');
    $vmin = $version["vi-min-controler"][2];
    $warning = "";
    if(floatval($unused) < floatval($vmin)) {
        $warning = $messages->getMessage('msg9');
    }
    $choices["replace-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth, $idem), 1, 1, $base, $warning];
}

if(isset($_POST["plate"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = State::firstOpenMonth($dir);
    $choices = [];

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
            if(!file_exists($dirMonth) || Lock::exists($dirMonth, 'month')) {
                break;
            }

            if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                if(Tarifs::v0_exists($dirMonth)) {
                    $dirVersion = globReverse($dirMonth)[0];
                    if(Lock::exists($dirVersion, 'version')) {
                        if(Unused::exists($dirMonth)) {
                            replace($choices, $year, $month, $dirMonth, true, 1);
                        }
                        else {
                            correct($choices, $year, $month, $dirMonth);
                        }
                    }
                    else {
                        $base = 0;
                        if(floatval(basename($dirVersion)) > 0) {
                            $base = 1;
                        }
                        fade($choices, $year, $month, $dirMonth, $base);
                    }
                }
                else {
                    loadOrReplace($choices, $year, $month, $dirMonth, false);
                }
            }
            else {
                loadOrReplace($choices, $year, $month, $dirMonth, false);
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
