<?php

require_once("../assets/Sap.php");
require_once("../assets/Unused.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Label.php");
require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../includes/State.php");
require_once("../session.inc");

if(isset($_POST["plate"]) && isset($_POST["type"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = new State($dir);
    $choices = [];
    $type = $_POST["type"];

    if(intval($mp->getLastMonth()) > 6) {
        $maxYear = State::addToString($mp->getLastYear(), 2);
        $maxMonth = State::addToMonth($mp->getLastMonth(), -6);
    }
    else {
        $maxYear = State::addToString($mp->getLastYear(), 1);
        $maxMonth = State::addToMonth($mp->getLastMonth(), 6);
    }

    $date = $maxYear.$maxMonth;

    while(intval($date) > intval($mp->getLastYear().$mp->getLastMonth())) {

        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);
        $dirMonth = $dir."/".$year."/".$month;

        if(file_exists($dirMonth."/".ParamZip::NAME) && (Unused::exists($dirMonth))) {
            $label = Label::load($dirMonth);
            if(empty($label)) {
                $label = "No label ?";
            }
            $prefix = "remove";
            if($type == "load") {
                $prefix = "replace";
            }
            $choices[$prefix."-".$year.$month] = [$month." ".$year, $label];
        }
        else {
            if($type == "load") {
                $choices["load-".$date] = [$month." ".$year, ""];
            }
        }

        if($month == "01") {
            $date -= 89;
        }
        else {
            $date--;
        }
    }
    $dirMonth = $dir."/".$mp->getLastYear()."/".$mp->getLastMonth();
    if(!Lock::exists($dirMonth, 'month')) {
        if(file_exists($dirMonth."/".ParamZip::NAME)) {
            $label = Label::load($dirMonth);
            if(empty($label)) {
                $label = "No label ?";
            }
            if($type == "load") {
                if(Unused::exists($dirMonth)) {
                    $prefix = "replace";
                }
                else {
                    $prefix = "correct";
                }
                $choices[$prefix."-".$mp->getLastYear().$mp->getLastMonth()] = [$mp->getLastMonth()." ".$mp->getLastYear(), $prefix];
            }
            if(($type == "remove") && (Unused::exists($dirMonth))) {
                $choices["remove-".$mp->getLastYear().$mp->getLastMonth()] = [$mp->getLastMonth()." ".$mp->getLastYear(), $label];
            }
        }
        else {
            if($type == "load") {
                $choices["correct-".$mp->getLastYear().$mp->getLastMonth()] = [$mp->getLastMonth()." ".$mp->getLastYear(), "load"];
            }
        }
    }
    echo json_encode($choices);
}
