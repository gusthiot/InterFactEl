<?php

require_once("../assets/Info.php");
require_once("../session.php");


if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $info = new Info();
    $html = '<div class="over"><table class="table infos">';
    foreach($info->load($dir) as $line) {
        $html .= '<tr>';
        $html .= '<td>'.str_replace('"', '', $line[1]).'</td><td>'.str_replace('"', '', $line[2]).'</td><td>'.$line[3].'</td>';
        $html .= '</tr>';

    }
    $html .= '</table></div>';
    echo $html;
}
