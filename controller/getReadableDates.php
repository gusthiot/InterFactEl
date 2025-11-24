<?php

require_once("../assets/ParamZip.php");
require_once("../assets/Lock.php");
require_once("../includes/State.php");
require_once("../session.inc");

if(isset($_POST["plate"])) { 

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $state = new State($dir);
    $choices = [];
    $html = "";

    foreach(globReverse($dir) as $dirYear) {
        $year = basename($dirYear);
        foreach(globReverse($dirYear) as $dirMonth) {
            $month = basename($dirMonth);
            if (file_exists($dirMonth."/".ParamZip::NAME)) {
                if($state->isLater($month, $year)) {
                    continue;
                }
                $choices[$year.$month] = [$year, $month];
            }
        }
    }
    if(count($choices) > 0) {
        $html = '<div id="dates">
                    <div id="tarifs-month">
                        <select id="tarifs-dates" class="custom-select lockable" >
                        <option disabled selected></option>';
        $i = 0;
        foreach($choices as $key=>$choice) {
            $html .= '<option value="'.$key.'">'.$choice[1]." ".$choice[0].'</option>';
        }
        $html .=        '</select>
                    </div>
                    <div id="tarifs-open">
                    </div>
                </div>';
        echo $html;
    }
    else {
        echo "Pas de données dans la période autorisée";
    }
}
