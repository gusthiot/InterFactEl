<?php

require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../session.inc");

/**
 * Called to display a selector to choose a period for a report
 */
if(isset($_POST["plate"]) && isset($_POST["type"])) { 
    $plateforme = $_POST["plate"];
    checkPlateforme("reporting", $plateforme);
    $dir = DATA.$plateforme;
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
        case "t1":
        case "t2":
        case "t3f":
        case "t3s":
            $vMin = 9.1;
            break;
        case "services":
            $vMin = 11.0;
            break;
        default:
            $vMin = 1.0;
    }

    foreach(globReverse($dir)  as $dirYear) {
        $year = basename($dirYear);
        $open = [];
        foreach(globReverse($dirYear) as $dirMonth) {
            $month = basename($dirMonth);
            $glob = glob($dirMonth."/*", GLOB_ONLYDIR);
            if (!empty($glob)) {
                $version = Lock::load($dirMonth, "month");
                if (!is_null($version)) {
                    if(!empty($open)) {
                        $infos = Info::load($open[2]."/".$open[3]);
                        $factel = $infos["FactEl"][2];
                        if(floatval($factel) >= $vMin) {
                            $choices[$open[0].$open[1]] = [$open[0], $open[1], $factel];
                        }
                        $open = [];
                    }
                    $dirVersion = $dirMonth."/".$version;
                    $run = Lock::load($dirVersion, "version");
                    $infos = Info::load($dirVersion."/".$run);
                    $factel = $infos["FactEl"][2];
                    if(floatval($factel) >= $vMin) {
                        $choices[$year.$month] = [$year, $month, $factel];
                    }
                }
                else {
                    foreach(array_reverse($glob) as $dirVersion) {
                        $run = Lock::load($dirVersion, "version");
                        if (!is_null($run)) {
                            $open = [$year, $month, $dirVersion, $run];
                            break;
                        }
                    }
                }
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
