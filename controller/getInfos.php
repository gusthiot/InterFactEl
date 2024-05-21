<?php

require_once("../src/Info.php");


if(isset($_POST["dir"])){
    $info = new Info();
    $html = '<div class="over"><table class="table infos">';
    foreach($info->load("../".$_POST["dir"]) as $line) {
        $html .= '<tr>';
        $html .= '<td>'.str_replace('"', '', $line[1]).'</td><td>'.str_replace('"', '', $line[2]).'</td><td>'.$line[3].'</td>';
        $html .= '</tr>';

    }
    $html .= '</table></div>';
    echo $html;
}
