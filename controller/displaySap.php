<?php

require_once("../session.php");
require_once("../assets/Sap.php");

if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $sap = new Sap();
    $html = '<div class="over"><table class="table factures"><tr>';
    $bills = $sap->load($dir);
    $lines = [];
    foreach($sap->getTitle() as $title) {
        $html .= '<th>'.str_replace('"', '', $title).'</th>';
    }
    $html .= '</tr>';
    foreach($bills as $bill) {
        $lines[$bill[0]][$bill[1]] = $bill;
    }
    ksort($lines);
    foreach($lines as $labo) {
        ksort($labo);
        foreach($labo as $line) {
            $html .= '<tr>';
            foreach($line as $key=>$cell) {
                ($key==2)?$html .= '<td>'.number_format(floatval($cell), 2, ".", "'").'</td>':$html .= '<td>'.$cell.'</td>';
            }
            $html .= '</tr>';
        }
    }
    $html .= '</table></div>';
    
    $html .= '<button type="button" id="getSap" class="btn but-line">Download File</button>';
    echo $html;
}
