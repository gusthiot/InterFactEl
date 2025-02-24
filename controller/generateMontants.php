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
if(isset($_POST["from"]) && isset($_POST["to"]) && isset($_POST["plate"])) {
    $plateforme = $_POST["plate"];
    checkPlateforme($dataGest, "reporting", $plateforme);

    $master = ["par-client"=>[], "par-class"=>[], "par-article"=>[], "par-client-class"=>[], "par-client-article"=>[], "par-article-class"=>[], "par-client-class-article"=>[]];

    include("../includes/reportShared.php");

    while(true) {

        include("../includes/reportDimensions.php");

        $montantsArray = [];
        if(!file_exists($dirRun."/REPORT/".$report[$factel]['montants']['prefix'].".csv")) {
            if(!file_exists($dirRun."/REPORT/")) {
                mkdir($dirRun."/REPORT/");
            }

            $crpArray = [];
            if($factel == 6) {
                if(file_exists($dirRun."/Bilans_Stats/".$bilansStats[$factel]['Bilancrp-f']['prefix'].$suffix.".csv")) {
                    $lines = Csv::extract($dirRun."/Bilans_Stats/".$bilansStats[$factel]['Bilancrp-f']['prefix'].$suffix.".csv");        
                    $columns = $bilansStats[$factel]['Bilancrp-f']['columns'];
                    for($i=1;$i<count($lines);$i++) {
                        $tab = explode(";", $lines[$i]);
                        $code = $tab[$columns['client-code']];
                        $dM = $tab[$columns["total-fact"]]-$tab[$columns["total-fact-l"]]-$tab[$columns["total-fact-c"]]-$tab[$columns["total-fact-w"]]-$tab[$columns["total-fact-x"]]-$tab[$columns["total-fact-r"]];
                        $crpArray[$code] = ["dM" => $dM, "dMontants"=>[$tab[$columns["total-fact-l"]], $tab[$columns["total-fact-c"]], $tab[$columns["total-fact-w"]], $tab[$columns["total-fact-x"]], $tab[$columns["total-fact-r"]]]];
                    }
                }
            }

            if($factel >= 9) {
                $columns = $bilansStats[$factel]['T1']['columns'];
                $lines = Csv::extract($dirRun."/Bilans_Stats/".$bilansStats[$factel]['T1']['prefix'].$suffix.".csv");
                $t1Array = [];
                for($i=1;$i<count($lines);$i++) {
                    $tab = explode(";", $lines[$i]);
                    $code = $tab[$columns['client-code']];
                    $clcl = $tab[$columns['client-class']];
                    $item = $tab[$columns['item-codeD']];
                    if(!array_key_exists($code, $t1Array)) {
                        $t1Array[$code] = [];
                    }
                    if(!array_key_exists($clcl, $t1Array[$code])) {
                        $t1Array[$code][$clcl] = [];
                    }
                    if(!array_key_exists($item, $t1Array[$code][$clcl])) {
                        $t1Array[$code][$clcl][$item] = 0;
                    }
                    $t1Array[$code][$clcl][$item] += floatval($tab[$columns["total-fact"]]);
                }
                foreach($t1Array as $code=>$pc) {
                    foreach($pc as $clcl=>$pcl) {
                        foreach($pcl as $item=>$tot) {
                            $montantsArray[] = [$code, $clcl, $item, $tot];
                        }
                    }
                }
            }
            else {
                $columns = $bilansStats[$factel]['Bilan-f']['columns'];
                $lines = Csv::extract($dirRun."/Bilans_Stats/".$bilansStats[$factel]['Bilan-f']['prefix'].$suffix.".csv");
                for($i=1;$i<count($lines);$i++) {
                    $tab = explode(";", $lines[$i]);
                    $code = $tab[$columns['client-code']];
                    if($code != $plateforme) {
                        if($factel < 7) {
                            $clcl = $clientsClasses[$code]['client-class'];
                        }
                        else {
                            $clcl = $tab[$columns['client-class']];
                        }
                        if($factel == 1) {
                            $montant = $tab[$columns["somme-t"]] + $tab[$columns["emolument-b"]] - $tab[$columns["emolument-r"]] - $tab[$columns["total-fact-l"]]
                                    - $tab[$columns["total-fact-c"]] - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]];
                            $montantsArray = facts($montantsArray, $montant, $tab, $columns, $clcl);
                        }
                        elseif($factel >=3 && $factel < 6) {
                            $montant = $tab[$columns["total-fact"]] -$tab[$columns["total-fact-l"]] - $tab[$columns["total-fact-c"]]
                                    - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]] - $tab[$columns["total-fact-r"]];
                            $montantsArray = facts($montantsArray, $montant, $tab, $columns, $clcl);
                        }
                        elseif($factel == 6) {
                            $montant = $tab[$columns["total-fact"]] - $tab[$columns["total-fact-l"]] - $tab[$columns["total-fact-c"]]
                                    - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]] - $tab[$columns["total-fact-r"]];
                            if(!empty($crpArray) && array_key_exists($code, $crpArray)) {
                                $montantsArray = facts($montantsArray, $montant, $tab, $columns, $clcl, $crpArray[$code]["dM"], $crpArray[$code]["dMontants"]);
                            } 
                            else {
                                $montantsArray = facts($montantsArray, $montant, $tab, $columns, $clcl);
                            }        
                        }
                        elseif($factel == 7 || $factel == 8) {
                            if($tab[$columns["platf-code"]] == $plateforme) {
                                $montant = $tab[$columns["total-fact"]];
                                if(($tab[$columns["item-codeD"]] == "R") && ($montant < 50)) {
                                    $montant = 0;
                                }
                                $montantsArray[] = [$code, $clcl, $tab[$columns["item-codeD"]], $montant];
                            }
                        }
                    }
                }
            }
            for($i=0;$i<count($montantsArray);$i++) {
                $montantsArray[$i][3] = round((2*$montantsArray[$i][3]),1)/2;
            }
            $montantsColumns = [$paramtext->getParam("client-code"), $paramtext->getParam("client-class"), $paramtext->getParam("item-codeD"), $paramtext->getParam("total-fact")];
            Csv::write($dirRun."/REPORT/".$report[$factel]['montants']['prefix'].".csv", array_merge([$montantsColumns], $montantsArray));
        }
        else {
            $lines = Csv::extract($dirRun."/REPORT/".$report[$factel]['montants']['prefix'].".csv"); 
            for($i=1;$i<count($lines);$i++) {
                $montantsArray[] = explode(";", $lines[$i]);
            }
        }

        foreach($montantsArray as $line) {
            $total += $line[3];
            $client = $clients[$line[0]];
            $class = $classes[$line[1]];
            $article = $articles[$line[2]];
            $montant = $line[3];
            $keys = ["par-client"=>$line[0], "par-class"=>$line[1], "par-article"=>$line[2], "par-client-class"=>$line[0]."-".$line[1], "par-client-article"=>$line[0]."-".$line[2],
                     "par-article-class"=>$line[2]."-".$line[1], "par-client-class-article"=>$line[0]."-".$line[1]."-".$line[2]];
            $extends = ["par-client"=>[$client], "par-class"=>[$class], "par-article"=>[$article], "par-client-class"=>[$client, $class], "par-client-article"=>[$client, $article],
                        "par-article-class"=>[$article, $class], "par-client-class-article"=>[$client, $class, $article]];
            $dimensions = ["par-client"=>[$d1], "par-class"=>[$d2], "par-article"=>[$d3], "par-client-class"=>[$d1, $d2], "par-client-article"=>[$d1, $d3],
                           "par-article-class"=>[$d3, $d2], "par-client-class-article"=>[$d1, $d2, $d3]];

            foreach($keys as $id=>$key) {
                $master = mapping($key, $master, $id, $extends, $dimensions, $monthly, $montant);
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

    sort($monthList);

    $onglets = ["par-client" => "Par Client", "par-class" => "Par Classe", "par-article" => "Par Article", "par-client-class" => "Par Client & Classe", 
                "par-client-article" => "Par Client & Article", "par-article-class" => "Par Article & Classe"];
    $columns = ["par-client" => ["client-name"], "par-class" => ["client-labelclass"], "par-article" => ["item-labelcode"], "par-client-class" => ["client-name", "client-labelclass"], 
                "par-client-article" => ["client-name", "item-labelcode"], "par-article-class" => ["item-labelcode", "client-labelclass"]];
    $columnsCsv = ["par-client" => $d1, "par-class" => $d2, "par-article" => ["item-codeD", "item-labelcode"], "par-client-class" => array_merge($d1, $d2),
                   "par-client-article" => array_merge($d1, ["item-codeD", "item-labelcode"]), "par-article-class" => array_merge(["item-codeD", "item-labelcode"], $d2)];

    $csv = csvHeader($paramtext, array_merge($d1, $d2, $d3), $monthList, "total-fact");
    foreach($master["par-client-class-article"] as $line) {
        if(floatval($line["total-fact"]) > 0) {
            $csv .= "\n".csvLine(array_merge($d1, $d2, $d3), $line, $monthList, "total-fact");
        }
    }
?>

<div class="total">Total facturé sur la période <?php echo $period; ?> : <?php echo number_format(floatval($total), 2, ".", "'"); ?> CHF</div>
<div class="total"><a href="data:text/plain;base64,<?php echo base64_encode($csv); ?>" download="total-montants.csv"><button type="button" id="total-montants" class="btn but-line">Download Csv</button></a></div>

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
    <?php echo generateTablesAndCsv($paramtext, $columns, $columnsCsv, $master, $monthList, "total-fact"); ?>
</div>

<?php
}

function facts($montantsArray, $montant, $tab, $columns, $clcl, $dM=0, $dMontants=[0, 0, 0, 0, 0]) 
{
    $code = $tab[$columns['client-code']];
    $facts = ["total-fact-l", "total-fact-c", "total-fact-w", "total-fact-x", "total-fact-r"];
    $types = ["L", "C", "W", "X", "R"];
    if(($montant - $dM) > 0) {
        $montantsArray[] = [$code, $clcl, "M", ($montant - $dM)];
    }
    foreach($facts as $pos=>$fact) {
        if(array_key_exists($fact, $columns)) {
            if(($tab[$columns[$fact]] - $dMontants[$pos]) > 0) {
                $montantsArray[] = [$code, $clcl, $types[$pos], ($tab[$columns[$fact]] - $dMontants[$pos])];
            }
        }
    }
    return $montantsArray;
}

function mapping($key, $master, $id, $extends, $dimensions, $monthly, $montant) 
{
    if(!array_key_exists($key, $master[$id])) {
        $master[$id][$key] = ["total-fact" => 0, "mois" => []];
        foreach($dimensions[$id] as $pos=>$dimension) {
            foreach($dimension as $d) {
                $master[$id][$key][$d] = $extends[$id][$pos][$d];
            }
        }
    }
    if(!array_key_exists($monthly, $master[$id][$key]["mois"])) {
        $master[$id][$key]["mois"][$monthly] = 0;
    }
    $master[$id][$key]["total-fact"] += $montant;
    $master[$id][$key]["mois"][$monthly] += $montant;
    return $master;
}

function sortTotal($a, $b) 
{
    return floatval($b["total-fact"]) - floatval($a["total-fact"]);
}
