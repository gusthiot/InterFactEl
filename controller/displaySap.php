<?php

require_once("../src/Sap.php");

if(isset($_POST["dir"])) {
    $sap = new Sap();
    $html = "<table>";
    $bills = $sap->load("../".$_POST["dir"]);
    $lines = [];
    foreach($bills as $bill) {
        $lines[$bill[0]][$bill[1]] = $bill;
    }
    ksort($lines);
    foreach($lines as $labo) {
        ksort($labo);
        foreach($labo as $line) {
            $html .= "<tr>";
            foreach($line as $cell) {
                $html .= "<td>".$cell."</td>";
            }
            $html .= "</tr>";
        }
    }
    $html .= "</table>";
    
    $html .= '<button type="button" id="getSap" class="btn btn-outline-dark">Download File</button>';
    echo $html;
}