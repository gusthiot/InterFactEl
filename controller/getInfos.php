<?php

require_once("../assets/Info.php");
require_once("../session.inc");

/**
 * Called to display a table with the metadata of a run
 */
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    checkPlateforme($dataGest, "facturation", $_POST["plate"]);
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $html = '<div class="over"><table class="table infos">';
    foreach(Info::load($dir) as $line) {
        $html .= '<tr>';
        $html .= '<td>'.str_replace('"', '', $line[1]).'</td><td>'.str_replace('"', '', $line[2]).'</td><td>'.$line[3].'</td>';
        $html .= '</tr>';

    }
    $html .= '</table></div>';
    echo $html;
}
