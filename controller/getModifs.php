<?php

require_once("../assets/Client.php");
require_once("../assets/Journal.php");
require_once("../assets/Modif.php");
require_once("../session.php");

if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $name = $gestionnaire->getGestionnaire($_SESSION['user'])['plates'][$_POST['plate']];
    $suf = "_".$name."_".$_POST['year']."_".$_POST['month']."_".$_POST['version'];
    $html = "";
    $modif = new Modif($dir."/Modif-factures".$suf.".csv");
    $html .= table($modif->getModifs(), "getModif", "Factures-modifs", "modifs", [7, 8]);

    $journal = new Journal($dir."/Journal-corrections".$suf.".csv");
    $html .= table($journal->getModifs(), "getJournal", "Journal-modifs", "journal", []);

    $client = new Client($dir."/Clients-modifs".$suf.".csv");
    $html .= table($client->getModifs(), "getClient", "Client-modifs", "client", []);

    if($html == "") {
        $html = "<p>Aucune modification</p>";
    }
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
