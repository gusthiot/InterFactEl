<?php

require_once("../src/Client.php");
require_once("../src/Journal.php");
require_once("../src/Modif.php");

if(isset($_POST["dir"]) && isset($_POST["suf"])){

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
    if(!empty($journal->getModifs())) {
        $html .= "<table>";
        foreach($journal->getModifs() as $line) {
            $html .= "<tr>";
            foreach($line as $cell) {
                $html .= "<td>".$cell."</td>";
            }
            $html .= "</tr>";

        }
        $html .= "</table>";
        $html .= '<button type="button" id="getJournal" class="btn btn-outline-dark">Download Journal-modifs</button>';
    }

    $client = new Client("../".$_POST["dir"]."/Client-modifs".$_POST["suf"].".csv");
    if(!empty($client->getModifs())) {
        $html .= "<table>";
        foreach($client->getModifs() as $line) {
            $html .= "<tr>";
            foreach($line as $cell) {
                $html .= "<td>".$cell."</td>";
            }
            $html .= "</tr>";

        }
        $html .= "</table>";
        $html .= '<button type="button" id="getClient" class="btn btn-outline-dark">Download Client-modifs</button>';
    }

    echo $html;
}