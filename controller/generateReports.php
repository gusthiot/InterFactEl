<?php

require_once("../assets/Csv.php");
require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../assets/ParamRun.php");
require_once("../includes/State.php");
require_once("../includes/Report.php");
require_once("../includes/ReportMontants.php");
require_once("../includes/ReportRabais.php");
require_once("../includes/ReportConsommations.php");
require_once("../includes/ReportRuns.php");
require_once("../includes/ReportUsages.php");
require_once("../session.inc");

/**
 * 
 */
if(isset($_POST["from"]) && isset($_POST["to"]) && isset($_POST["plate"]) && isset($_POST["type"])) {
    $plateforme = $_POST["plate"];
    checkPlateforme($dataGest, "reporting", $plateforme);
    switch($_POST["type"]) {
        case "montants":
            $report = new ReportMontants($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "rabais":
            $report = new ReportRabais($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "consommations":
            $report = new ReportConsommations($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "runs":
            $report = new ReportRuns($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "usages":
            $report = new ReportUsages($plateforme, $_POST["to"], $_POST["from"]);
            break;
        default:
            exit("Type de rapport non pris en compte !");
    }

    $report->loopOnMonths();
    $report->display();
}
