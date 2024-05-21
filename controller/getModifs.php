<?php

require_once("../src/Client.php");
require_once("../src/Journal.php");
require_once("../src/Modif.php");

if(isset($_POST["dir"]) && isset($_POST["suf"])){
    $html = "";
    $modif = new Modif("../".$_POST["dir"]."/Modif-factures".$_POST["suf"].".csv");
    $html .= table($modif->getModifs(), "getModif", "Factures-modifs", "modifs", [7, 8]);

    $journal = new Journal("../".$_POST["dir"]."/Journal-corrections".$_POST["suf"].".csv");
    $html .= table($journal->getModifs(), "getJournal", "Journal-modifs", "journal", []);

    $client = new Client("../".$_POST["dir"]."/Clients-modifs".$_POST["suf"].".csv");
    $html .= table($client->getModifs(), "getClient", "Client-modifs", "client", []);

    echo $html;
}

function table(array $modifs, string $id, string $title, string $class, array $prices): string
{
    $html = "";
    if(count($modifs)>1) {
        $html .= '<div class="over"><table class="table '.$class.'">';
        $prev = null;
        foreach($modifs as $key=>$line) {
            if($key%2 != 0) {
                $prev = $line;
            }
            $html .= "<tr>";
            foreach($line as $col=>$cell) {
                $color = "";
                if(($id != "getModif") && ($key>0) && ($key%2 == 0) && ($col != 2) && ($cell != $prev[$col])) {
                    $color = ' class="yellow"';
                }
                in_array($col, $prices) ? $case = number_format(floatval($cell), 2, ".", "'") : 
                    (($col==1) ? $case = ((intval($cell) < 10) ? "0".$cell : $cell) : $case = $cell);
                ($key==0) ? $html .= "<th>".$cell."</th>" : $html .= "<td".$color.">".$case."</td>";
            }
            $html .= "</tr>";

        }
        $html .= "</table></div>";
        $html .= '<button type="button" id="'.$id.'" class="btn but-line">Download '.$title.'</button>';
    }
    return $html;
}
