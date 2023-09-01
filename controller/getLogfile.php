<?php

require_once("../src/Logfile.php");
require_once("../src/Label.php");

$txt = "";

if(isset($_POST['plate'])) {
    $logfile = new Logfile();
    $label = new Label();
    $plate = "../".$_POST['plate'];
    $lines = explode(PHP_EOL, $logfile->load($plate));
    $txt = "";
    foreach($lines as $line) {
        $parts = explode("|", $line);
        if(count($parts) === 7) {
            $dir = $plate."/".str_replace(", ", "/", trim($parts[2]));
            if(($lab = $label->load($dir)) != "") {
                $parts[3] = " ".$lab." ";
                $txt .= implode("|", $parts)."<br />";
            }
            else {
                $txt .= $line."<br />";
            }
        }
        else {
            $txt .= $line."<br />";
        }
    }
}

echo $txt;