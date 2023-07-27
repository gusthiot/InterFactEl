<?php

require_once("../from_csv/Info.php");

if(!empty($_POST["csv"])){
    $info = new Info("../".$_POST["csv"]);
    $html = "<table>";
    foreach($info->getInfos() as $label=>$value) {
        $html .= "<tr>";
        $html .= "<td>".$label."</td><td>".$value."</td>";
        $html .= "</tr>";

    }
    $html .= "</table>";
    echo $html;
}