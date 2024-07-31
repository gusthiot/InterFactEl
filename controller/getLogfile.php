<?php

require_once("../assets/Logfile.php");
require_once("../assets/Label.php");
require_once("../session.inc");

/**
 * Called to display the actions history of a plateform
 */
checkGest($dataGest);
$txt = "";
if(isset($_POST['plate'])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    $plate = DATA.$_POST['plate'];
    $lines = explode(PHP_EOL, Logfile::load($plate));
    $txt = "<div id='over-log'><div id='log'>";
    foreach($lines as $line) {
        $parts = explode("|", $line);
        if(count($parts) === 7) {
            $dir = $plate."/".str_replace(", ", "/", trim($parts[2]));
            if(trim($parts[4]) == "Renvoi dans SAP") {
                $parts[4] = "<span class='red'>".$parts[4]."</span>";
            }
            if(($label = Label::load($dir)) != "") {
                $parts[3] = " ".$label." ";
            }
            $txt .= implode("|", $parts)."<br />";
        }
        else {
            $txt .= "&nbsp;&nbsp;&nbsp;&nbsp;".$line."<br />";
        }
    }
    $txt .= "</div></div>";
}

echo $txt;
