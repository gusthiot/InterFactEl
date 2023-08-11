<?php

require_once("../src/Sap.php");

if(!empty($_POST["csv"])){
    $sap = new Sap("../".$_POST["csv"]);
    $html = "<table>";
    foreach($sap->getBills() as $line) {
        $html .= "<tr>";
        foreach($line as $cell) {
            $html .= "<td>".$cell."</td>";
        }
        $html .= "</tr>";

    }
    $html .= "</table>";
    
    $html .= '<button type="button" id="getSap" class="btn btn-outline-dark">Download File</button>';
    echo $html;
}