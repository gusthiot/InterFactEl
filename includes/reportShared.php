<?php


$plateName = $dataGest['reporting'][$plateforme];
$state = new State(DATA.$plateforme);
$paramtext = new ParamRun($state->getLastPath()."/IN/", 'text');

$bilansStats = getJsonStructure("../bilans-stats.json");
$in = getJsonStructure("../in.json");
$report = getJsonStructure("../report.json");

$d1 = ["client-code", "client-sap", "client-name", "client-name2"];
$d2 = ["client-class", "client-labelclass"];
$d3 = ["item-codeD", "item-order", "item-labelcode"];

$clients = [];
$classes = [];
$clientsClasses = [];
$articles = [];
$monthList = [];

$total = 0;
$period = substr($_POST["from"], 4, 2)."/".substr($_POST["from"], 0, 4)." - ".substr($_POST["to"], 4, 2)."/".substr($_POST["to"], 0, 4);
$date = $_POST["to"];


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
    for($i=1;$i<count($lines);$i++) {
        $tab = explode(";", $lines[$i]);
        $code = $tab[$columns[$names[0]]];
        if(!array_key_exists($code, $result)) {
            $data = [];
            foreach($names as $key) {
                $data[$key] = str_replace('"', '', $tab[$columns[$key]]);
            }
            $result[$code] = $data;
        }
    }
    return $result;
}

function csvHeader($paramtext, $columns, $monthList, $totalKey) 
{
    $header = "";
    $first = true;
    foreach($columns as $name) {
            $first ? $first = false : $header .= ";";
            $header .= $paramtext->getParam($name);
    }
    $header .= ";".$paramtext->getParam($totalKey);
    foreach($monthList as $monthly) {
        $header .= ";".$monthly;
    }
    return $header;
}

function csvLine($columns, $line, $monthList, $totalKey) 
{
    $data = "";
    $first = true;
    foreach($columns as $name) {
        $first ? $first = false : $data .= ";";
        $data .= $line[$name];
    }
    $data .= ";".$line[$totalKey];
    foreach($monthList as $monthly) {
        $data .= ";";
        if(array_key_exists($monthly, $line["mois"])) {
            $data .= $line["mois"][$monthly];
        }
    }
    return $data;
}

function generateTablesAndCsv($paramtext, $columns, $columnsCsv, $master, $monthList, $totalKey) 
{
    $html = "";
    $show = "show active";
    foreach($columns as $id=>$names) {
        uasort($master[$id], 'sortTotal');
        $html .= '<div class="tab-pane fade '.$show.'" id="'.$id.'" role="tabpanel" aria-labelledby="'.$id.'-tab">
                    <div class="over report-large"><table class="table report-table" id="'.$id.'-table"><thead><tr>';
        $show = "";
        $csv = csvHeader($paramtext, $columnsCsv[$id], $monthList, $totalKey);
        foreach($names as $name) {
            $html .= "<th class='sort-text'>".$paramtext->getParam($name)."</th>";
        }      
        $html .= "<th class='right sort-number'>".$paramtext->getParam($totalKey)."</th></tr></thead><tbody>";
        foreach($master[$id] as $line) {
            $html .= "<tr>";
            foreach($names as $name) {
                $html .= "<td>".$line[$name]."</td>";
            }
            $html .= "<td class='right'>".number_format(floatval($line[$totalKey]), 2, ".", "'")."</td></tr>";
            $csv .= "\n".csvLine($columnsCsv[$id], $line, $monthList, $totalKey);
        }
        $html .= "</tbody></table></div>";
        $html .= '<a href="data:text/plain;base64,'.base64_encode($csv).'" download="'.$id.'.csv"><button type="button" id="'.$id.'-dl"  class="btn but-line">Download Csv</button></a></div>';
    }
    return $html;
}

