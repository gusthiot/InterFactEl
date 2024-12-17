<?php

require_once("../session.inc");

if(isset($_POST["plate"])) { 
    checkPlateforme($dataGest, "reporting", $_POST["plate"]);
    $dir = DATA.$_POST['plate'];
    $choices = [];
    $html = "";

    foreach(array_reverse(glob($dir."/*", GLOB_ONLYDIR))  as $dirYear) {
        $year = basename($dirYear);
        foreach(array_reverse(glob($dirYear."/*", GLOB_ONLYDIR)) as $dirMonth) {
            $month = basename($dirMonth);
            if (file_exists($dirMonth."/lockm.csv")) {
                $choices[$year.$month] = [$year, $month];
            }
        }
    }
    if(count($choices) > 0) {
        $html = '<div id="dates">
                    <div id="first">
                        <label for="from">De</label>
                        <select id="from" class="custom-select lockable" <?= $disabled ?> >
                        <option disabled selected></option>';
        $i = 0;
        foreach($choices as $key=>$choice) {
            $html .= '<option value="'.$key.'">'.$choice[1]." ".$choice[0].'</option>';
        }
        $html .=        '</select>
                    </div>
                    <div id="last">
                    </div>
                    <div id="generate">
                    </div>
                </div>';
        echo $html;
    }
}
