<?php

require_once("../assets/Modif.php");
require_once("../session.inc");

/**
 * Called to display modifications files as tables
 */
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    checkPlateforme($dataGest, "facturation", $_POST["plate"]);
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $name = $dataGest['facturation'][$_POST['plate']];
    $suf = "_".$name."_".$_POST['year']."_".$_POST['month']."_".$_POST['version'];
    $html = "";
    $html .= table(Modif::load($dir."/Modif-factures".$suf.".csv"), "get-modif", "Factures-modifs", "modifs", [7, 8]);
    $html .= table(Modif::load($dir."/Journal-corrections".$suf.".csv"), "get-journal", "Journal-modifs", "journal", []);
    $html .= table(Modif::load($dir."/Clients-modifs".$suf.".csv"), "get-client", "Client-modifs", "client", []);

    if($html == "") {
        $html = "<p>Aucune modification</p>";
    }
    echo $html;
}

/**
 * Creates table from data
 *
 * @param array $modifs data array
 * @param string $id button id to download the data file
 * @param string $title button title for download
 * @param string $class table class
 * @param array $prices columns with financial format
 * @return string
 */
function table(array $modifs, string $id, string $title, string $class, array $prices): string
{
    $html = "";
    if(count($modifs)>1) {
        $html .= '<div class="over"><table class="table '.$class.'"><thead>';
        $prev = null;
        foreach($modifs as $key=>$line) {
            if($key%2 != 0) {
                $prev = $line;
            }
            if($key==1) {
                $html .= "</thead><tbody>";
            }
            $html .= "<tr>";
            foreach($line as $col=>$cell) {
                $color = "";
                // display the differencies in yellow 
                if(($class != "modifs") && ($key>0) && ($key%2 == 0) && ($col != 2) && ($cell != $prev[$col])) {
                    $color = ' class="yellow"';
                }
                // check for financial format, for month column, and for titles line  
                in_array($col, $prices) ? $case = number_format(floatval($cell), 2, ".", "'") : 
                    (($col==1) ? $case = ((intval($cell) < 10) ? "0".$cell : $cell) : $case = $cell);
                ($key==0) ? $html .= "<th>".$cell."</th>" : $html .= "<td".$color.">".$case."</td>";
            }
            $html .= "</tr>";

        }
        $html .= "</tbody></table></div>";
        $html .= '<button type="button" id="'.$id.'" class="btn but-line">Download '.$title.'</button>';
    }
    return $html;
}
