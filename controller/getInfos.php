<?php

require_once("../src/Info.php");

if(isset($_POST["dir"])){
    $info = new Info();
    $html = "<table>";
    foreach($info->load("../".$_POST["dir"]) as $line) {
        $html .= "<tr>";
        $html .= "<td>".$line[1]."</td><td>".$line[2]."</td>";
        $html .= "</tr>";

    }
    $html .= "</table>";
    echo $html;
}