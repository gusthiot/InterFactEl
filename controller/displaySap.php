<?php

require_once("../src/Sap.php");

if(isset($_POST["dir"])) {
    $sap = new Sap();
    $html = "<table>";
    foreach($sap->load("../".$_POST["dir"]) as $line) {
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