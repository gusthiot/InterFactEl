<?php

require_once("../assets/Csv.php");
require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../assets/ParamText.php");
require_once("../includes/State.php");
require_once("../reports/Report.php");
require_once("../reports/ReportMontants.php");
require_once("../reports/ReportRabais.php");
require_once("../reports/ReportConsommations.php");
require_once("../reports/ReportRuns.php");
require_once("../reports/ReportUsages.php");
require_once("../reports/ReportConsommables.php");
require_once("../reports/ReportServices.php");
require_once("../reports/ReportPenalites.php");
require_once("../reports/ReportTransactions.php");
require_once("../reports/ReportPlateforme.php");
require_once("../reports/ReportClients.php");
require_once("../reports/ReportPropres.php");
require_once("../session.inc");

/**
 * Called to generate a given report for a given period
 */
if(isset($_POST["from"]) && isset($_POST["to"]) && isset($_POST["plate"]) && isset($_POST["type"])) {
    $plateforme = $_POST["plate"];
    checkPlateforme("reporting", $plateforme);
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
        case "consommables":
            $report = new ReportConsommables($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "services":
            $report = new ReportServices($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "penalites":
            $report = new ReportPenalites($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "transactions":
            $report = new ReportTransactions($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "plateforme":
            $report = new ReportPlateforme($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "clients":
            $report = new ReportClients($plateforme, $_POST["to"], $_POST["from"]);
            break;
        case "propres":
            $report = new ReportPropres($plateforme, $_POST["to"], $_POST["from"]);
            break;
        default:
            exit("Type de rapport non pris en compte !");
    }

    $report->loopOnMonths();
    $report->display();
}
