<?php

require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../session.inc");

/**
 * Called to display a selector to choose a period for statuts
 */
if(isset($_POST["plate"])) {
    $plateforme = $_POST["plate"];
    checkPlateforme("reporting", $plateforme);
    $dir = DATA.$plateforme;
    $choices = [];
    $html = "";
    foreach(globReverse($dir) as $dirYear) {
        $year = basename($dirYear);
        foreach(globReverse($dirYear) as $dirMonth) {
            $month = basename($dirMonth);
            $glob = glob($dirMonth."/*", GLOB_ONLYDIR);
            if(!empty($glob)) {
                $choices[$year.$month] = [$year, $month];
            }
        }
    }

    if(count($choices) > 0) {
        $html = '<div id="dates">
                    <div id="statut-first">
                        <label for="statut-from">De</label>
                        <select id="statut-from" class="custom-select lockable" <?= $disabled ?> >
                        <option disabled selected></option>';
        $i = 0;
        foreach($choices as $key=>$choice) {
            $html .= '<option value="'.$key.'">'.$choice[1]." ".$choice[0].'</option>';
        }
        $html .=        '</select>
                    </div>
                    <div id="statut-last">
                    </div>
                    <div id="statut-buttons">
                    </div>
                </div>';
        echo $html;
    }
    else {
        echo "Pas de données dans la période autorisée";
    }
}
