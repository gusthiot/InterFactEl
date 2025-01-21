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
if(isset($_POST["from"]) && isset($_POST["to"]) && isset($_POST["plate"]) /*&& isset($_POST["bilan"])*/) {
    $plateforme = $_POST["plate"];
    checkPlateforme($dataGest, "reporting", $plateforme);
    $plateName = $dataGest['reporting'][$plateforme];
    $state = new State(DATA.$plateforme);
    $paramtext = new ParamRun($state->getLastPath()."/IN/", 'text');

    $bilansStats = getJsonStructure("../bilans-stats.json");
    $in = getJsonStructure("../in.json");
    $report = getJsonStructure("../report.json");

    $clientsNames = ["client-code", "client-sap", "client-name", "client-name2"];
    $clients = [];

    $classesNames = ["client-idclass", "client-class", "client-labelclass"];
    $classes = [];

    $clientsClassesNames = ["client-code", "client-idclass"];
    $clientsClasses = [];

    //$montantsNames = ["client-code", "client-class", "item-codeD", "total-fact"];
    //$montants = [];
    
    $parAll = ['client'=>[], 'class'=>[], 'client-class'=>[]];
    $parClient = [];
    $parClass = [];
    $parClientClass = [];
    $monthlies = [];

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
        $monthlies[] = $monthly;
        $clients = getDirectoryCsv("/IN/",$dirRun, $in[$factel]['client'], $clientsNames, $clients);
        $classes = getDirectoryCsv("/IN/",$dirRun, $in[$factel]['classeclient'], $classesNames, $classes);
        if($factel < 8) {
            $clientsClasses = getDirectoryCsv("/IN/",$dirRun, $in[$factel]['clientclasse'], $clientsClassesNames, $clientsClasses);
        }

        $doMontants = false;
        if(!file_exists($dirRun."/REPORT/".$report[$factel]['montants']['prefix'].".csv")) {
            if(!file_exists($dirRun."/REPORT/")) {
                mkdir($dirRun."/REPORT/");
            }
            $doMontants = true;
            $montantsColumns = [$paramtext->getParam("client-code"), $paramtext->getParam("client-class"), $paramtext->getParam("item-codeD"), $paramtext->getParam("total-fact")];
            $montantsArray = [$montantsColumns];
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
                        $crpArray[$code] = ["dt" => $tab[$columns["total-fact"]], "dl" => $tab[$columns["total-fact-l"]], "dc" => $tab[$columns["total-fact-c"]], 
                                            "dw" => $tab[$columns["total-fact-w"]], "dx" => $tab[$columns["total-fact-x"]] ];
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
                if($factel < 8) {
                    $idclass = $clientsClasses[$code]['client-idclass'];
                }
                else {
                    $idclass = $tab[$columns['client-idclass']];
                }
                if($factel > 1) {
                    $montant = $tab[$columns["total-fact"]];
                }
                else {
                    $montant = $tab[$columns["total-fact-p"]] + $tab[$columns["total-fact-q"]] + $tab[$columns["total-fact-o"]] + $tab[$columns["total-fact-n"]]
                                + $tab[$columns["total-fact-l"]] + $tab[$columns["total-fact-c"]] + $tab[$columns["total-fact-w"]] + $tab[$columns["total-fact-x"]];
                }
                if(array_key_exists($code, $parAll['client'])) {
                    $parAll['client'][$code]["total-fact"] += $montant;
                    $parAll['client'][$code][$monthly] = $montant;
                } 
                else {
                    $parAll['client'][$code] = ["total-fact"=>$montant, $monthly=>$montant];
                }
                if(array_key_exists($idclass, $parAll['class'])) {
                    $parAll['class'][$idclass]["total-fact"] += $montant;
                    $parAll['class'][$idclass][$monthly] = $montant;
                } 
                else {
                    $parAll['class'][$idclass] = ["total-fact"=>$montant, $monthly=>$montant];
                }
                $key = $code."-".$idclass;
                if(array_key_exists($key, $parAll['client-class'])) {
                    $parAll['client-class'][$key]["total-fact"] += $montant;
                    $parAll['client-class'][$key][$monthly] = $montant;
                } 
                else {
                    $parAll['client-class'][$key] = ["total-fact"=>$montant, $monthly=>$montant];
                }
                if($doMontants) {
                    if($factel == 1) {
                        $clcl = $tab[$columns['client-class']];
                        $montant = $tab[$columns["somme-t"]] + $tab[$columns["emolument-b"]] - $tab[$columns["emolument-r"]] - $tab[$columns["total-fact-l"]]
                                - $tab[$columns["total-fact-c"]] - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]];
                        if($montant > 0) {
                            $montantsArray[] = [$code, $clcl, "M", $montant];
                        }
                        if($tab[$columns["total-fact-l"]] > 0) {
                            $montantsArray[] = [$code, $clcl, "L", $tab[$columns["total-fact-l"]]];
                        }
                        if($tab[$columns["total-fact-c"]] > 0) {
                            $montantsArray[] = [$code, $clcl, "C", $tab[$columns["total-fact-c"]]];
                        }
                        if($tab[$columns["total-fact-w"]] > 0) {
                            $montantsArray[] = [$code, $clcl, "W", $tab[$columns["total-fact-w"]]];
                        }
                        if($tab[$columns["total-fact-x"]] > 0) {
                            $montantsArray[] = [$code, $clcl, "X", $tab[$columns["total-fact-x"]]];
                        }
                    }
                    elseif($factel >=3 && $factel <= 5) {
                        $montant = $tab[$columns["total-fact"]] -$tab[$columns["total-fact-l"]] - $tab[$columns["total-fact-c"]]
                                 - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]];
                        if($montant > 0) {
                            $montantsArray[] = [$code, $clcl, "M", $montant];
                        }
                        if($tab[$columns["total-fact-l"]] > 0) {
                            $montantsArray[] = [$code, $clcl, "L", $tab[$columns["total-fact-l"]]];
                        }
                        if($tab[$columns["total-fact-c"]] > 0) {
                            $montantsArray[] = [$code, $clcl, "C", $tab[$columns["total-fact-c"]]];
                        }
                        if($tab[$columns["total-fact-w"]] > 0) {
                            $montantsArray[] = [$code, $clcl, "W", $tab[$columns["total-fact-w"]]];
                        }
                        if($tab[$columns["total-fact-x"]] > 0) {
                            $montantsArray[] = [$code, $clcl, "X", $tab[$columns["total-fact-x"]]];
                        }
                    }
                    elseif($factel == 6) {
                        $montant = $tab[$columns["total-fact"]] - $tab[$columns["total-fact-l"]] - $tab[$columns["total-fact-c"]]
                                 - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]];
                        $dM = $dl = $dc = $dw = $dx = 0;
                        if(!empty($crpArray) && array_key_exists($code, $crpArray)) {
                            $dl = $crpArray[$code]["dl"];
                            $dc = $crpArray[$code]["dc"];
                            $dw = $crpArray[$code]["dw"];
                            $dx = $crpArray[$code]["dx"];
                            $dM = $crpArray[$code]["dt"]-$dl-$dc-$dw-$dx;
                        }
                        if(($montant-$dM) > 0) {
                            $montantsArray[] = [$code, $clcl, "M", $montant-$dM];
                        }
                        if(($tab[$columns["total-fact-l"]]-$dl) > 0) {
                            $montantsArray[] = [$code, $clcl, "L", $tab[$columns["total-fact-l"]]-$dl];
                        }
                        if(($tab[$columns["total-fact-c"]]-$dc) > 0) {
                            $montantsArray[] = [$code, $clcl, "C", $tab[$columns["total-fact-c"]]-$dc];
                        }
                        if(($tab[$columns["total-fact-w"]]-$dw) > 0) {
                            $montantsArray[] = [$code, $clcl, "W", $tab[$columns["total-fact-w"]]-$dw];
                        }
                        if(($tab[$columns["total-fact-x"]]-$dx) > 0) {
                            $montantsArray[] = [$code, $clcl, "X", $tab[$columns["total-fact-x"]]-$dx];
                        }
                    }
                    elseif($factel == 7 || $factel == 8) {
                        if($tab[$columns["platf-code"]] == $plateforme) {
                            $montantsArray[] = [$code, $clcl, $tab[$columns["item-codeD"]], $tab[$columns["total-fact"]]];
                        }
                    }
                }
            }
        }
        if($doMontants && $factel >= 9) {
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
                    $t1Array[$code][$clcl][$item] += intval($tab[$columns["total-fact"]]);
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
        if($doMontants) {
            Csv::write($dirRun."/REPORT/".$report[$factel]['montants']['prefix'].".csv", $montantsArray);
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

    $clientsColumns = [$paramtext->getParam("client-name"), $paramtext->getParam("client-name2"), $paramtext->getParam("total-fact")];
    $clientsCsv = [array_merge([$paramtext->getParam("client-code"), $paramtext->getParam("client-sap")], $clientsColumns, $monthlies)];    
    $clientsData = [];
    uasort($parAll['client'], 'sortTotal');
    foreach($parAll['client'] as $code=>$par) {
        $client = $clients[$code];
        $columnsData = [$client["client-name"], str_replace('"', '', $client["client-name2"]), $par["total-fact"]];
        $columnsMonthly = [];
        foreach($monthlies as $monthly) {
            if(array_key_exists($monthly, $par)) {
                $columnsMonthly[] = $par[$monthly];
            }
            else {
                $columnsMonthly[] = "";
            }
        }
        $clientsData[] = $columnsData;
        $clientsCsv[] = array_merge([$client["client-code"], $client["client-sap"]], $columnsData, $columnsMonthly);
    }
    $clientsHtml = generateTable($clientsColumns, $clientsData, "par-client", "show active");
    Csv::write(TEMP."/par-client_".time().".csv", $clientsCsv);

   
    $classesColumns = [$paramtext->getParam("client-class"), $paramtext->getParam("client-labelclass"), $paramtext->getParam("total-fact")];
    $classesData = []; 
    uasort($parAll['class'], 'sortTotal');
    foreach($parAll['class'] as $id=>$par) {
        $class = $classes[$id];
        $classesData[] = [$class["client-class"], $class["client-labelclass"], $par["total-fact"]];
    }
    $classesHtml = generateTable($classesColumns, $classesData, "par-class");

    $clientsClassesColumns = [$paramtext->getParam("client-name"), $paramtext->getParam("client-labelclass"), $paramtext->getParam("total-fact")];
    $clientsClassesData = []; 
    uasort($parAll['client-class'], 'sortTotal');
    foreach($parAll['client-class'] as $key=>$parKey) {
        $tab = explode("-", $key);
        $client = $clients[$tab[0]];
        $class = $classes[$tab[1]];
        $clientsClassesData[] = [$client["client-name"], $class["client-labelclass"], $parKey["total-fact"]];
    }
    $clientsClassesHtml = generateTable($clientsClassesColumns, $clientsClassesData, "par-client-class");

?>


<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="par-client-tab" data-toggle="tab" href="#par-client" role="tab" aria-controls="par-client" aria-selected="true">Par Client</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="par-class-tab" data-toggle="tab" href="#par-class" role="tab" aria-controls="par-class" aria-selected="false">Par Classe</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="par-client-class-tab" data-toggle="tab" href="#par-client-class" role="tab" aria-controls="par-client-class" aria-selected="false">Par Client Par Classe</a>
  </li>
</ul>

<div class="tab-content p-3">
    <?php echo $clientsHtml; ?>
    <?php echo $classesHtml; ?>
    <?php echo $clientsClassesHtml; ?>
</div>

<?php
}

function sortTotal($a, $b) {
    return floatval($b["total-fact"]) - floatval($a["total-fact"]);
}

function generateTable($columnsNames, $columnsArray, $id, $show="") {
    $html = "";
    $html .= '<div class="tab-pane fade '.$show.'" id="'.$id.'" role="tabpanel" aria-labelledby="'.$id.'-tab">
                <div class="over"><table class="table report-table" id="'.$id.'-table"><thead><tr>';
    foreach($columnsNames as $name) {
        $html .= "<th>".$name."</th>";
    }
    $html .= "</tr></thead><tbody>";
    foreach($columnsArray as $columnsData) {
        $html .= "<tr>";
        foreach($columnsData as $data) {
            if(is_float($data)) {
                $html .= "<td>".number_format(floatval($data), 2, ".", "'")."</td>";
            }
            else {
                $html .= "<td>".$data."</td>";
            }
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table></div></div>";
    return $html;
}

function getJsonStructure($name) {
    $structure = "";
    if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
        $structure = json_decode(fread($open, filesize($name)), true);
        fclose($open);
    }
    return $structure;
}

function getDirectoryCsv($subDir, $dirRun, $fileData, $names, $result) {
    $columns = $fileData['columns'];
    $name = $fileData['prefix'];
    $lines = Csv::extract($dirRun.$subDir.$name.".csv");
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
                    $data[$key] = $tab[$columns[$key]];
                }
                $result[$code] = $data;
            }
        }
    }
    return $result;
}
