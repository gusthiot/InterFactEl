<?php

require_once("../src/Client.php");
require_once("../src/Journal.php");
require_once("../src/Modif.php");

if(isset($_POST["dir"]) && isset($_POST["suf"])){
    $html = "";
    $modif = new Modif("../".$_POST["dir"]."/Modif-factures".$_POST["suf"].".csv");
    $html .= table($modif->getModifs(), "getModif", "Modif-modifs", "modifs", [7, 8]);

    $journal = new Journal("../".$_POST["dir"]."/Journal-corrections".$_POST["suf"].".csv");
    $html .= table($journal->getModifs(), "getJournal", "Journal-modifs", "journal", []);

    $client = new Client("../".$_POST["dir"]."/Clients-modifs".$_POST["suf"].".csv");
    $html .= table($client->getModifs(), "getClient", "Client-modifs", "client", []);

    echo $html;
}

function table(array $modifs, string $id, string $title, string $class, array $prices): string
{
    $html = "";
    if(!empty($modifs)) {
        $html .= '<table class="table '.$class.'">';
        foreach($modifs as $key=>$line) {
            $html .= "<tr>";
            foreach($line as $col=>$cell) {
                in_array($col, $prices) ? $case = number_format(floatval($cell), 2, ".", "'") : 
                    (($col==1) ? $case = ((intval($cell) < 10) ? "0".$cell : $cell) : $case = $cell);
                ($key==0) ? $html .= "<th>".$cell."</th>" : $html .= "<td>".$case."</td>";
            }
            $html .= "</tr>";

        }
        $html .= "</table>";
        $html .= '<button type="button" id="'.$id.'" class="btn but-line">Download '.$title.'</button>';
    }
    return $html;
}
