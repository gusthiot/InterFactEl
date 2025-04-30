<?php

require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../session.inc");

if(isset($_POST["plate"]) && isset($_POST["type"]) && isset($_POST["title"])) { 
    checkPlateforme($dataGest, "reporting", $_POST["plate"]);
    $dir = DATA.$_POST['plate'];
    $choices = [];
    $html = "";
    switch($_POST["type"]) {
        case "consommations":
            $vMin = 7.0;
            break;
        default:
            $vMin = 1.0;
    }

    foreach(array_reverse(glob($dir."/*", GLOB_ONLYDIR))  as $dirYear) {
        $year = basename($dirYear);
        foreach(array_reverse(glob($dirYear."/*", GLOB_ONLYDIR)) as $dirMonth) {
            $month = basename($dirMonth);
            if (file_exists($dirMonth."/".Lock::FILES['month'])) {
                $dirVersion = array_reverse(glob($dirMonth."/*", GLOB_ONLYDIR))[0];
                $run = Lock::load($dirVersion, "version");
                $infos = Info::load($dirVersion."/".$run);
                $factel = $infos["FactEl"][2];
                if($factel >= $vMin) {
                    $choices[$year.$month] = [$year, $month, $factel];
                }
            }
        }
    }
    if(count($choices) > 0) {
        $html = '<div id="date-title">'.$_POST["title"].'</div><div id="dates">
                    <div id="first">
                        <label for="from">De</label>
                        <select id="from" class="custom-select lockable" <?= $disabled ?> >
                        <option disabled selected></option>';
        $i = 0;
        foreach($choices as $key=>$choice) {
            $html .= '<option value="'.$key.'">'.$choice[1]." ".$choice[0].' (V'.$choice[2].')</option>';
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
