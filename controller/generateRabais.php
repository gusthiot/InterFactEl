<?php

require_once("../assets/Csv.php");
require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../assets/ParamRun.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * 
 */
if(isset($_POST["from"]) && isset($_POST["to"]) && isset($_POST["plate"]) && isset($_POST["unique"])) {
    $plateforme = $_POST["plate"];
    $tmpDir =  TEMP.$_POST["unique"];
    if(!mkdir($tmpDir, 0777, true)) {
        $_SESSION['alert-danger'] = "Impossible d'écrire les rapports !";
        header('Location: ../reporting.php?plateforme='.$_POST["plate"]);
        exit;
    }
    checkPlateforme($dataGest, "reporting", $plateforme);
    $dsub = ["deduct-CHF", "subsid-deduct", "discount-bonus", "subsid-bonus"];
    $master = ["reimbursed"=>[], "subsid"=>[], "rabais-subsid"=>[]];

    include("../includes/reportShared.php");

    while(true) {

        include("../includes/reportDimensions.php");

        $rabaisArray = [];
        if(!file_exists($dirRun."/REPORT/".$report[$factel]['rabaisbonus']['prefix'].".csv")) {
            if(!file_exists($dirRun."/REPORT/")) {
                mkdir($dirRun."/REPORT/");
            }
            if($factel == 7 || $factel == 8) {
                $columns = $bilansStats[$factel]['Bilan-f']['columns'];
                $lines = Csv::extract($dirRun."/Bilans_Stats/".$bilansStats[$factel]['Bilan-f']['prefix'].$suffix.".csv");
                for($i=1;$i<count($lines);$i++) {
                    $tab = explode(";", $lines[$i]);
                    if(($tab[$columns["platf-code"]] == $plateforme) && ($tab[$columns['client-code']] != $plateforme )) {
                        $rabaisArray[] = [$tab[$columns['client-code']], $tab[$columns['client-class']], $tab[$columns["item-codeD"]], $tab[$columns["deduct-CHF"]],
                                            $tab[$columns["subsid-deduct"]], $tab[$columns["discount-bonus"]], $tab[$columns["subsid-bonus"]]];
                    }
                }
            }
            else {
                $columns = $bilansStats[$factel]['Bilan-s']['columns'];
                $lines = Csv::extract($dirRun."/Bilans_Stats/".$bilansStats[$factel]['Bilan-s']['prefix'].$suffix.".csv");
                for($i=1;$i<count($lines);$i++) {
                    $tab = explode(";", $lines[$i]);
                    $code = $tab[$columns['client-code']];
                    if($factel < 7) {
                        if($factel == 6) {
                            $msm = $tab[$columns["subsides-m"]];
                        }
                        else {
                            $msm = $tab[$columns["subsides-ma"]] + $tab[$columns["subsides-mo"]];
                        }
                        $clcl = $clientsClasses[$code]['client-class'];
                        $mbm = $tab[$columns["bonus-m"]];
                        $msc = $tab[$columns["subsides-c"]];
                        if($mbm > 0 || $msm > 0) {
                            $rabaisArray[] = [$code, $clcl, "M", 0, 0, $mbm, $msm];
                        }
                        if($msc > 0) {
                            $rabaisArray[] = [$code, $clcl, "C", 0, 0, 0, $msc];
                        }
                    }
                    else {
                        if($code != $plateforme) {
                            $rabaisArray[] = [$code, $tab[$columns['client-class']], $tab[$columns["item-codeD"]], $tab[$columns["deduct-CHF"]],
                                                $tab[$columns["subsid-deduct"]], $tab[$columns["discount-bonus"]], $tab[$columns["subsid-bonus"]]];
                        }
                    }
                }
            }

            $rabaisColumns = [$paramtext->getParam("client-code"), $paramtext->getParam("client-class"), $paramtext->getParam("item-codeD"), 
                                $paramtext->getParam("deduct-CHF"), $paramtext->getParam("subsid-deduct"), $paramtext->getParam("discount-bonus"), $paramtext->getParam("subsid-bonus")];
            Csv::write($dirRun."/REPORT/".$report[$factel]['rabaisbonus']['prefix'].".csv", array_merge([$rabaisColumns], $rabaisArray));
        }
        else {
            $lines = Csv::extract($dirRun."/REPORT/".$report[$factel]['rabaisbonus']['prefix'].".csv");
            for($i=1;$i<count($lines);$i++) {
                $rabaisArray[] = explode(";", $lines[$i]);
            }
        }

        foreach($rabaisArray as $line) {
            $total += $line[3]+$line[4]+$line[5]+$line[6];
            $client = $clients[$line[0]];
            $class = $classes[$line[1]];
            $article = $articles[$line[2]];
            $montants = ["deduct-CHF"=>$line[3], "subsid-deduct"=>$line[4], "discount-bonus"=>$line[5], "subsid-bonus"=>$line[6]];
            $keys = ["reimbursed"=>$line[0], "subsid"=>$line[0], "rabais-subsid"=>$line[0]."-".$line[1]."-".$line[2]];
            $extends = ["reimbursed"=>[$client], "subsid"=>[$client], "rabais-subsid"=>[$client, $class, $article]];
            $dimensions = ["reimbursed"=>[$d1], "subsid"=>[$d1], "rabais-subsid"=>[$d1, $d2, $d3]];
            $sums = ["reimbursed"=>["discount-bonus", "subsid-bonus"], "subsid"=>$dsub, "rabais-subsid"=>$dsub];

            foreach($keys as $id=>$key) {
                $master = mapping($key, $master, $id, $extends, $dimensions, $monthly, $montants, $sums);
            }

        }

        if($date == $_POST["from"]) {
            break;
        }

        if($month == "01") {
            $date -= 89;
        }
        else {
            $date--;
        }
    }

    $onglets = ["reimbursed" => "A rembourser", "subsid" => "Rabais et subsides par client"];
    $columns = ["reimbursed" => ["client-name"], "subsid" => ["client-name"]];
    $columnsCsv = ["reimbursed" => array_merge($d1, ["discount-bonus", "subsid-bonus"]), "subsid" => array_merge($d1, $dsub)];

    $csv = [csvHeader($paramtext, array_merge($d1, $d2, $d3, $dsub), $monthList, "total-subsid")];
    foreach($master["rabais-subsid"] as $line) {
        $csv[] = csvLine(array_merge($d1, $d2, $d3, $dsub), $line, $monthList, "total-subsid");
    }
    Csv::write($tmpDir."/total.csv", $csv);

?>

<div class="total">Total rabais et subsides sur la période <?php echo $period; ?> : <?php echo number_format(floatval($total), 2, ".", "'"); ?> CHF</div>
<div class="total"><button type="button" id="total-dl" class="btn but-line get-report">Download Csv</button></div>

<ul class="nav nav-tabs" role="tablist">
<?php
    $active = "active";
    foreach($onglets as $id => $title) { 
?>
  <li class="nav-item">
    <a class="nav-link <?= $active ?>" id="<?= $id ?>-tab" data-toggle="tab" href="#<?= $id ?>" role="tab" aria-controls="<?= $id ?>" aria-selected="true"><?= $title ?></a>
  </li>
<?php
        $active = "";
    }
?>
</ul>

<div class="tab-content p-3">
    <?php echo generateTablesAndCsv($paramtext, $columns, $columnsCsv, $master, $monthList, $tmpDir, "total-subsid"); ?>
</div>

<?php
}


function mapping($key, $master, $id, $extends, $dimensions, $monthly, $montants, $sums) 
{
    if(!array_key_exists($key, $master[$id])) {
        $master[$id][$key] = ["total-subsid" => 0, "mois" => []];
        
        foreach($dimensions[$id] as $pos=>$dimension) {
            foreach($dimension as $d) {
                $master[$id][$key][$d] = $extends[$id][$pos][$d];
            }
        }
        foreach($montants as $mt=>$val) {
            if(in_array($mt, $sums[$id])) {
                $master[$id][$key][$mt] = 0;
            }
        }
    }
        
    if(!array_key_exists($monthly, $master[$id][$key]["mois"])) {
        $master[$id][$key]["mois"][$monthly] = 0;
    }
    $total = 0;
    foreach($montants as $mt=>$val) {
        if(in_array($mt, $sums[$id])) {
            $master[$id][$key][$mt] += $val;
            $total += $val;
        }
    }
    $master[$id][$key]["total-subsid"] += $total;
    $master[$id][$key]["mois"][$monthly] += $total;
    return $master;
}

function sortTotal($a, $b) 
{
    return floatval($b["total-subsid"]) - floatval($a["total-subsid"]);
}
