<?php

require_once("../src/Sap.php");
require_once("../src/Lock.php");

if(isset($_POST["dir"])){
    $dir = "../".$_POST["dir"];
    $sap = new Sap();
    $lock = new Lock();
    $loctxt = $lock->load($dir, "run");
    $html = "<div>";
    $i = 0;
    foreach($sap->load($dir) as $line) {
        if( $line[3] === "SENT" || ($loctxt && $line[3] === "READY") ) {
            $html .= '<div><input type="checkbox" id=bill"'.$i.'" name="bills" value="'.$line[1].'"><label for="bill'.$i.'"> '.$line[1].' </label></div>';
        }
        $i++;

    }
    $html .= "</div>";
    
    $html .= '<button type="button" id="sendBills" class="btn btn-outline-dark">Envoyer</button>';
    echo $html;
}