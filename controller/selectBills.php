<?php

require_once("../src/Sap.php");
require_once("../src/Lock.php");

if(isset($_POST["dir"]) && isset($_POST["type"])){
    $sap = new Sap();
    $html = "";
    $choices = [];
    $i = 0;
    $bills = $sap->load("../".$_POST["dir"]);
    $lines = [];
    foreach($bills as $bill) {
        $lines[$bill[0]][$bill[1]] = $bill;
    }
    ksort($lines);
    foreach($lines as $labo) {
        ksort($labo);
        foreach($labo as $line) {
            if($_POST["type"] == "sendBills") {
                if($line[3] === "READY" || $line[3] === "ERROR") {
                    $choices[] = '<div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="bill'.$i.'" name="bills" value="'.$line[1].'">
                                    <label class="custom-control-label" for="bill'.$i.'"> '.$line[0].' '.$line[1].' '.$line[2].' '.$line[3].' </label>
                                </div>';
                }
            }
            else {
                $lock = new Lock();
                $loctxt = $lock->load("../".$_POST["dir"], "run");
                if( $line[3] === "SENT" || ($loctxt && $line[3] === "READY") ) {
                    $choices[] = '<div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="bill'.$i.'" name="bills" value="'.$line[1].'">
                                    <label class="custom-control-label" for="bill'.$i.'"> '.$line[0].' '.$line[1].' '.$line[2].' '.$line[3].' </label>
                                </div>';
                }
            }

            $i++;
        }
    }
    if(count($choices)>0) {
        $html .= "<div>";
        $html .= '<br /><div><button type="button" id="allBills" class="btn but-line lockable">Tout sélectionner</button><button type="button" id="'.$_POST["type"].'" class="btn but-line lockable">Envoyer</button></div>';
    
        foreach($choices as $choice) {
            $html .= $choice;
        }
        $html .= "</div>";
    }
    
    echo $html;
}
