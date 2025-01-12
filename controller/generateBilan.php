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

    $clientsNames = ["client-code", "client-sap", "client-name", "client-name2"];
    $clients = [];

    $classesNames = ["client-idclass", "client-class", "client-labelclass"];
    $classes = [];

    $clientsClassesNames = ["client-code", "client-idclass"];
    $clientsClasses = [];
    
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
        $clients = getInCsv($dirRun, $in[$factel]['client'], $clientsNames, $clients);
        $classes = getInCsv($dirRun, $in[$factel]['classeclient'], $classesNames, $classes);
        if($factel < 8) {
            $clientsClasses = getInCsv($dirRun, $in[$factel]['clientclasse'], $clientsClassesNames, $clientsClasses);
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

function getInCsv($dirRun, $fileData, $names, $result) {
    $columns = $fileData['columns'];
    $name = $fileData['prefix'];
    $lines = Csv::extract($dirRun."/IN/".$name.".csv");
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
