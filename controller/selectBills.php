<?php

require_once("../src/Sap.php");
require_once("../config.php");

if(isset($_POST["dir"])){
    $sap = new Sap();
    $html = "<div>";
    $i = 0;
    foreach($sap->load(GROUND.$_POST["dir"]) as $line) {
        if($line[3] == "READY" || $line[3] == "ERROR") {
            $html .= '<div><input type="checkbox" id=bill"'.$i.'" name="bills" value="'.$line[1].'"><label for="bill'.$i.'"> '.$line[1].' </label></div>';
        }
        $i++;

    }
    $html .= "</div>";
    
    $html .= '<button type="button" id="sendBills" class="btn btn-outline-dark">Envoyer</button>';
    echo $html;
}