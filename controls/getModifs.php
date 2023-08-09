<?php

require_once("../from_csv/Journal.php");
require_once("../from_csv/Modif.php");

if(!empty($_POST["dir"]) && !empty($_POST["suf"])){

    $modif = new Modif("../".$_POST["dir"]."/Modif-factures".$_POST["suf"].".csv");
    $html = "<table>";
    foreach($modif->getModifs() as $line) {
        $html .= "<tr>";
        foreach($line as $cell) {
            $html .= "<td>".$cell."</td>";
        }
        $html .= "</tr>";

    }
    $html .= "</table>";
    
    $html .= '<button type="button" id="getModif" class="btn btn-outline-dark">Download Modif-factures</button>';

    $journal = new Journal("../".$_POST["dir"]."/Journal-modifs".$_POST["suf"].".csv");
    $html .= "<table>";
    foreach($journal->getModifs() as $line) {
        $html .= "<tr>";
        foreach($line as $cell) {
            $html .= "<td>".$cell."</td>";
        }
        $html .= "</tr>";

    }
    $html .= "</table>";
    
    $html .= '<button type="button" id="getModif" class="btn btn-outline-dark">Download Journal-modifs</button>';


    echo $html;
}