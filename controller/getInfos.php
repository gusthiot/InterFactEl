<?php

require_once("../src/Info.php");


if(isset($_POST["dir"])){
    $info = new Info();
    $html = '<table class="table">';
    foreach($info->load("../".$_POST["dir"]) as $line) {
        $html .= '<tr>';
        $html .= '<th>'.str_replace('"', '', $line[1]).'</th><td>'.str_replace('"', '', $line[2]).'</td><td>'.$line[3].'</td>';
        $html .= '</tr>';

    }
    $html .= '</table>';
    echo $html;
}
