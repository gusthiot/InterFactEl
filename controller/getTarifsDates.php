<?php

require_once("../assets/Sap.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../includes/State.php");
require_once("../session.inc");

if(isset($_POST["plate"]) && isset($_POST["type"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = State::lastRun($dir);
    $choices = [];
    $html = "";
    $type = $_POST["type"];

    foreach(globReverse($dir) as $dirYear) {
        $year = basename($dirYear);
        foreach(globReverse($dirYear) as $dirMonth) {
            $month = basename($dirMonth);
            if (file_exists($dirMonth."/".ParamZip::NAME) && ($type == "control")) {
                if(State::isLaterThan($month, $year, $mp['month'], $mp['year']) || State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                    if($type == "control") {
                       $choices[$year.$month] = [$year, $month];
                    }
                }
            }
            if($type == "read") {
                $lastRun = 0;
                $lastVersion = 0;
                foreach(globReverse($dirMonth) as $dirVersion) {
                    foreach(globReverse($dirVersion) as $dirRun) {
                        $sap = new Sap($dirRun);
                        $infos = Info::load($dirRun);
                        $factel = $infos["FactEl"][2];
                        if((floatval($factel) > 11.02) && (file_exists($dirRun."/lock.csv") || $sap->status() > 1)) {
                            $choices[$year.$month] = [$year.$month, $factel];
                            break;
                        }
                    }
                }
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
