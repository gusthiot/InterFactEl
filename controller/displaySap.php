<?php

require_once("../src/Sap.php");

if(isset($_POST["dir"])) {
    $sap = new Sap();
    $html = '<table class="table factures"><tr>';
    $bills = $sap->load("../".$_POST["dir"]);
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
    $html .= '</table>';
    
    $html .= '<button type="button" id="getSap" class="btn but-line">Download File</button>';
    echo $html;
}
