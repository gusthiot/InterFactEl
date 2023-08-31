<?php

require_once("../src/Sap.php");
require_once("../src/Lock.php");

if(isset($_POST["dir"]) && isset($_POST["type"])){
    $sap = new Sap();
    $html = "<div>";
    $choices = [];
    $i = 0;
    foreach($sap->load("../".$_POST["dir"]) as $line) {
        if($_POST["type"]) {
            if($line[3] === "READY" || $line[3] === "ERROR") {
                $choices[] = '<div><input type="checkbox" id="bill'.$i.'" name="bills" value="'.$line[1].'"><label for="bill'.$i.'"> '.$line[1].' </label></div>';
            }
        }
        else {
            $lock = new Lock();
            $loctxt = $lock->load($dir, "run");
            if( $line[3] === "SENT" || ($loctxt && $line[3] === "READY") ) {
                $choices[] = '<div><input type="checkbox" id="bill'.$i.'" name="bills" value="'.$line[1].'"><label for="bill'.$i.'"> '.$line[1].' </label></div>';
            }
        }

        $i++;

    }
    if(count($choices)>1) {
        $html .= '<br /><div><button type="button" id="allBills" class="btn btn-outline-dark">Tout sélectionner</button></div>';
    }
    foreach($choices as $choice) {
        $html .= $choice;
    }
    $html .= "</div>";
    
    $html .= '<button type="button" id="'.$_POST["type"].'" class="btn btn-outline-dark">Envoyer</button>';
    echo $html;
}