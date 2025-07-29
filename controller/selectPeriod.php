<?php

require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../session.inc");

if(isset($_POST["plate"]) && isset($_POST["type"]) && isset($_POST["title"])) { 
    checkPlateforme("reporting", $_POST["plate"]);
    $dir = DATA.$_POST['plate'];
    $choices = [];
    $html = "";
    switch($_POST["type"]) {
        case "consommations":
        case "penalites":
            $vMin = 7.0;
            break;
        case "propres":
            $vMin = 8.0;
            break;
        case "services":
            $vMin = 11.0;
            break;
        default:
            $vMin = 1.0;
    }

    foreach(array_reverse(glob($dir."/*", GLOB_ONLYDIR))  as $dirYear) {
        $year = basename($dirYear);
        $open = [];
        foreach(array_reverse(glob($dirYear."/*", GLOB_ONLYDIR)) as $dirMonth) {
            $month = basename($dirMonth);
            $glob = glob($dirMonth."/*", GLOB_ONLYDIR);
            if (!empty($glob)) {
                if (file_exists($dirMonth."/".Lock::FILES['month'])) {
                    if(!empty($open)) {
                        $infos = Info::load($open[0]);
                        $factel = $infos["FactEl"][2];
                        if($factel >= $vMin) {
                            $choices[$open[1].$open[2]] = [$open[1], $open[2], $factel];
                        }
                        $open = [];
                    }
                    $dirVersion = array_reverse($glob)[0];
                    $run = Lock::load($dirVersion, "version");
                    $infos = Info::load($dirVersion."/".$run);
                    $factel = $infos["FactEl"][2];
                    if($factel >= $vMin) {
                        $choices[$year.$month] = [$year, $month, $factel];
                    }
                }
                else {
                    foreach(array_reverse($glob) as $dirVersion) {
                        if (file_exists($dirVersion."/".Lock::FILES['version'])) {
                            $run = Lock::load($dirVersion, "version");
                            $open = [$run, $year, $month];
                            break;
                        }
                    }
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
    else {
        echo "Pas de données dans la période autorisée";
    }
}
