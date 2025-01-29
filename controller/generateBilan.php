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
    $plateName = $dataGest['reporting'][$plateforme];
    $state = new State(DATA.$plateforme);
    $paramtext = new ParamRun($state->getLastPath()."/IN/", 'text');

    $bilansStats = getJsonStructure("../bilans-stats.json");
    $in = getJsonStructure("../in.json");
    $report = getJsonStructure("../report.json");

    $d1 = ["client-code", "client-sap", "client-name", "client-name2"];
    $d2 = ["client-class", "client-labelclass"];
    $d3 = ["item-codeD", "item-order", "item-labelcode"];
    $master = ["par-client"=>[], "par-class"=>[], "par-article"=>[], "par-client-class"=>[], "par-client-article"=>[], "par-article-class"=>[], "par-client-class-article"=>[]];

    $clients = [];
    $classes = [];
    $clientsClasses = [];
    $articles = [];
    $monthList = [];

    $total = 0;
    $period = substr($_POST["from"], 4, 2)."/".substr($_POST["from"], 0, 4)." - ".substr($_POST["to"], 4, 2)."/".substr($_POST["to"], 0, 4);

    $date = $_POST["to"];
    while(true) {
        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);
        $dir = DATA.$plateforme."/".$year."/".$month;
        $dirVersion = array_reverse(glob($dir."/*", GLOB_ONLYDIR))[0];
        $run = Lock::load($dirVersion, "version");
        $dirRun = $dirVersion."/".$run;

        $infos = Info::load($dirRun);
        $factel = $infos["FactEl"][2];

        $versionTab = explode("/", $dirVersion);
        $version = $versionTab[count($versionTab)-1];
        if($factel > 8) {
            $suffix = "_".$plateName."_".$year."_".$month."_".$version;
        }
        else {
            $suffix = "_".$year."_".$month;
        }
        $monthly = $year."-".$month;
        $monthList[] = $monthly;
        $clients = getDirectoryCsv($dirRun."/IN/", $in[$factel]['client'], $clients);
        $classes = getDirectoryCsv($dirRun."/IN/", $in[$factel]['classeclient'], $classes);
        if($factel < 7) {
            $clientsClasses = getDirectoryCsv($dirRun."/IN/", $in[$factel]['clientclasse'], $clientsClasses);
        }
        if($factel == 8) {
            $articlesTemp = [];
            $ordersTemp = [];
            $articlesTemp = getDirectoryCsv($dirRun."/IN/", $in[$factel]['articlesap'], $articlesTemp);
            $ordersTemp = getDirectoryCsv($dirRun."/IN/", $in[$factel]['ordersap'], $ordersTemp);
            foreach($articlesTemp as $code=>$line) {
                if(!array_key_exists($code, $articles)) {
                    $data = $line;
                    $data["item-order"] = $ordersTemp[$code]["item-order"];
                    $result[$code] = $data;
                }
            }
        }
        else {
            $articles = getDirectoryCsv($dirRun."/IN/", $in[$factel]['articlesap'], $articles);
        }

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
                    $first = true;
                    foreach($lines as $line) {
                        $tab = explode(";", $line);
                        if($first) {
                            $first = false;
                        }
                        else {
                            $code = $tab[$columns['client-code']];
                            $dM = $tab[$columns["total-fact"]]-$tab[$columns["total-fact-l"]]-$tab[$columns["total-fact-c"]]-$tab[$columns["total-fact-w"]]-$tab[$columns["total-fact-x"]];
                            $crpArray[$code] = ["dM" => $dM, "dMontants"=>[$tab[$columns["total-fact-l"]], $tab[$columns["total-fact-c"]], $tab[$columns["total-fact-w"]], $tab[$columns["total-fact-x"]]]];
                        }
                    }
                }
            }

            $columns = $bilansStats[$factel]['Bilan-f']['columns'];
            $name = $bilansStats[$factel]['Bilan-f']['prefix'];
            $lines = Csv::extract($dirRun."/Bilans_Stats/".$name.$suffix.".csv");
            $first = true;
            foreach($lines as $line) {
                $tab = explode(";", $line);
                if($first) {
                    $first = false;
                }
                else {
                    $code = $tab[$columns['client-code']];
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
                    elseif($factel >=3 && $factel <= 5) {
                        $montant = $tab[$columns["total-fact"]] -$tab[$columns["total-fact-l"]] - $tab[$columns["total-fact-c"]]
                                 - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]];
                        $montantsArray = facts($montantsArray, $montant, $tab, $columns, $clcl);
                    }
                    elseif($factel == 6) {
                        $montant = $tab[$columns["total-fact"]] - $tab[$columns["total-fact-l"]] - $tab[$columns["total-fact-c"]]
                                 - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]];
                        if(!empty($crpArray) && array_key_exists($code, $crpArray)) {
                            $montantsArray = facts($montantsArray, $montant, $tab, $columns, $clcl, $crpArray[$code]["dM"], $crpArray[$code]["dMontants"]);
                        } 
                        else {
                            $montantsArray = facts($montantsArray, $montant, $tab, $columns, $clcl);
                        }        
                    }
                    elseif($factel == 7 || $factel == 8) {
                        if($tab[$columns["platf-code"]] == $plateforme) {
                            $montantsArray[] = [$code, $tab[$columns['client-class']], $tab[$columns["item-codeD"]], $tab[$columns["total-fact"]]];
                        }
                    }
                }
            }

            if($factel >= 9) {
                $lines = Csv::extract($dirRun."/Bilans_Stats/".$bilansStats[$factel]['T1']['prefix'].$suffix.".csv");        
                $columns = $bilansStats[$factel]['T1']['columns'];
                $t1Array = [];
                $first = true;
                foreach($lines as $line) {
                    $tab = explode(";", $line);
                    if($first) {
                        $first = false;
                    }
                    else {
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
                }
                foreach($t1Array as $code=>$pc) {
                    foreach($pc as $clcl=>$pcl) {
                        foreach($pcl as $item=>$tot) {
                            $montantsArray[] = [$code, $clcl, $item, $tot];
                        }
                    }
                }
            }

            $montantsColumns = [$paramtext->getParam("client-code"), $paramtext->getParam("client-class"), $paramtext->getParam("item-codeD"), $paramtext->getParam("total-fact")];
            Csv::write($dirRun."/REPORT/".$report[$factel]['montants']['prefix'].".csv", [$montantsColumns] + $montantsArray);
        }
        else {
            $lines = Csv::extract($dirRun."/REPORT/".$report[$factel]['montants']['prefix'].".csv");
            $first = true;
            foreach($lines as $line) {
                $tab = explode(";", $line);
                if($first) {
                    $first = false;
                }
                else {
                    $montantsArray[] = $tab;
                }
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

    $onglets = ["par-client" => "Par Client", "par-class" => "Par Classe", "par-article" => "Par Article", "par-client-class" => "Par Client & Classe", 
                "par-client-article" => "Par Client & Article", "par-article-class" => "Par Article & Classe"];
    $columns = ["par-client" => ["client-name"], "par-class" => ["client-labelclass"], "par-article" => ["item-labelcode"], "par-client-class" => ["client-name", "client-labelclass"], 
                "par-client-article" => ["client-name", "item-labelcode"], "par-article-class" => ["item-labelcode", "client-labelclass"]];
    $columnsCsv = ["par-client" => $d1, "par-class" => $d2, "par-article" => ["item-codeD", "item-labelcode"], "par-client-class" => $d1 + $d2,
                   "par-client-article" => $d1 + ["item-codeD", "item-labelcode"], "par-article-class" => ["item-codeD", "item-labelcode"] + $d2];

    $csv = [csvHeader($paramtext, array_merge($d1, $d2, $d3), $monthList)];
    foreach($master["par-client-class-article"] as $line) {
        $csv[] = csvLine(array_merge($d1, $d2, $d3), $line, $monthList);
    }
    Csv::write($tmpDir."/total.csv", $csv);

?>

<div class="total">Total facturé sur la période <?php echo $period; ?> : <?php echo number_format(floatval($total), 2, ".", "'"); ?> CHF</div>
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
    <?php echo generateTablesAndCsv($paramtext, $columns, $columnsCsv, $master, $monthList, $tmpDir); ?>
</div>

<?php
}

function facts($montantsArray, $montant, $tab, $columns, $clcl, $dM=0, $dMontants=[0, 0, 0, 0]) 
{
    $code = $tab[$columns['client-code']];
    $facts = ["total-fact-l", "total-fact-c", "total-fact-w", "total-fact-x"];
    $types = ["L", "C", "W", "X"];
    if(($montant - $dM) > 0) {
        $montantsArray[] = [$code, $clcl, "M", ($montant - $dM)];
    }
    foreach($facts as $pos=>$fact) {
        if(($tab[$columns[$fact]] - $dMontants[$pos]) > 0) {
            $montantsArray[] = [$code, $clcl, $types[$pos], ($tab[$columns[$fact]] - $dMontants[$pos])];
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

function csvHeader($paramtext, $columns, $monthList) 
{
    $header = [];
    foreach($columns as $name) {
            $header[] = $paramtext->getParam($name);
    }
    $header[] = $paramtext->getParam("total-fact");
    foreach($monthList as $monthly) {
        $header[] = $monthly;
    }
    return $header;
}

function csvLine($columns, $line, $monthList) 
{
    $data = [];
    foreach($columns as $name) {
        $data[] = $line[$name];
    }
    $data[] = $line["total-fact"];
    foreach($monthList as $monthly) {
        if(array_key_exists($monthly, $line["mois"])) {
            $data[] = $line["mois"][$monthly];
        }
        else {
            $data[] = "";
        }
    }
    return $data;
}

function generateTablesAndCsv($paramtext, $columns, $columnsCsv, $master, $monthList, $tmpDir) 
{
    $html = "";
    $show = "show active";
    foreach($columns as $id=>$names) {
        uasort($master[$id], 'sortTotal');
        $html .= '<div class="tab-pane fade '.$show.'" id="'.$id.'" role="tabpanel" aria-labelledby="'.$id.'-tab">
                    <div class="over report-large"><table class="table report-table" id="'.$id.'-table"><thead><tr>';
        $show = "";
        $csv = [csvHeader($paramtext, $columnsCsv[$id], $monthList)];
        foreach($names as $name) {
            $html .= "<th class='sort-text'>".$paramtext->getParam($name)."</th>";
        }      
        $html .= "<th class='right sort-number'>".$paramtext->getParam("total-fact")."</th></tr></thead><tbody>";
        foreach($master[$id] as $line) {
            $html .= "<tr>";
            foreach($names as $name) {
                $html .= "<td>".$line[$name]."</td>";
            }
            $html .= "<td class='right'>".number_format(floatval($line["total-fact"]), 2, ".", "'")."</td></tr>";
            $csv[] = csvLine($columnsCsv[$id], $line, $monthList);
        }
        $html .= "</tbody></table></div>";
        $html .= '<button type="button" id="'.$id.'-dl" class="btn but-line get-report">Download Csv</button></div>';
        Csv::write($tmpDir."/".$id.".csv", $csv);
    }
    return $html;
}

function getJsonStructure($name) 
{
    $structure = "";
    if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
        $structure = json_decode(fread($open, filesize($name)), true);
        fclose($open);
    }
    return $structure;
}

function getDirectoryCsv($dir, $fileData, $result) 
{
    $columns = $fileData['columns'];
    $names = array_keys($columns);
    $name = $fileData['prefix'];
    $lines = Csv::extract($dir.$name.".csv");
    $first = true;
    foreach($lines as $line) {
        $tab = explode(";", $line);
        if($first) {
            $first = false;
        }
        else {
            $code = $tab[$columns[$names[0]]];
            if(!array_key_exists($code, $result)) {
                $data = [];
                foreach($names as $key) {
                    $data[$key] = str_replace('"', '', $tab[$columns[$key]]);
                }
                $result[$code] = $data;
            }
        }
    }
    return $result;
}
