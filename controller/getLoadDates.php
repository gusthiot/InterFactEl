<?php

require_once("../assets/Sap.php");
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
    $mp = State::lastRun($dir);
    $choices = [];
    $type = $_POST["type"];

    if(intval($mp['month']) > 6) {
        $maxYear = State::addToString($mp['year'], 2);
        $maxMonth = State::addToMonth($mp['month'], -6);
    }
    else {
        $maxYear = State::addToString($mp['year'], 1);
        $maxMonth = State::addToMonth($mp['month'], 6);
    }

    $date = $maxYear.$maxMonth;

    while(intval($date) > intval($mp['year'].$mp['month'])) {

        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);
        $dirMonth = $dir."/".$year."/".$month;

        if(file_exists($dirMonth."/".ParamZip::NAME) && (file_exists($dirMonth."/unused.csv"))) {
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
    $dirMonth = $dir."/".$mp['year']."/".$mp['month'];
    $label = Label::load($dirMonth);
    if(empty($label)) {
        $label = "No label ?";
    }
    if(file_exists($dirMonth."/".ParamZip::NAME)) {
        if($type == "load") {
            if(file_exists($dirMonth."/unused.csv")) {
                $prefix = "replace";
            }
            else {
                $prefix = "correct";
            }
            $choices[$prefix."-".$mp['year'].$mp['month']] = [$mp['month']." ".$mp['year'], $label];
        }
        if(($type == "remove") && (file_exists($dirMonth."/unused.csv"))) {
            $choices["remove-".$mp['year'].$mp['month']] = [$mp['month']." ".$mp['year'], $label];
        }
    }
    else {
        if($type == "load") {
            $choices["correct-".$mp['year'].$mp['month']] = [$mp['month']." ".$mp['year'], $label];
        }
    }
    echo json_encode($choices);
}
