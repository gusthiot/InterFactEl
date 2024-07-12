<?php

require_once("../session.inc");
require_once("../assets/Sap.php");

/**
 * Called to display a table with the list of the bills for a run
 */
checkGest($dataGest);
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $sap = new Sap($dir);
    $html = '<div class="over"><table class="table factures"><thead><tr>';
    $lines = [];
    foreach($sap->getTitle() as $title) {
        $html .= '<th>'.str_replace('"', '', $title).'</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach($sap->getBills() as $bill) {
        $lines[$bill[0]][$bill[1]] = $bill;
    }
    ksort($lines);
    foreach($lines as $labo) {
        ksort($labo);
        foreach($labo as $line) {
            $html .= '<tr>';
            foreach($line as $key=>$cell) {
                // only column 2 with financial format
                ($key==2)?$html .= '<td>'.number_format(floatval($cell), 2, ".", "'").'</td>':$html .= '<td>'.$cell.'</td>';
            }
            $html .= '</tr>';
        }
    }
    $html .= '</table></tbody></div>';
    
    $html .= '<button type="button" id="get-sap" class="btn but-line">Download File</button>';
    echo $html;
}
else {
    echo "post_data_missing";
}
