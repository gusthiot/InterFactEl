<?php

require_once("../assets/Sap.php");
require_once("../assets/Lock.php");
require_once("../session.inc");

/**
 * Called to display a table with a checklist for the bills, to select it to send it to SAP
 */
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"]) && isset($_POST["type"])){
    checkPlateforme($dataGest, "facturation", $_POST["plate"]);
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $sap = new Sap($dir);
    $html = "";
    $choices = [];
    $i = 0;
    $lines = [];
    foreach($sap->getBills() as $bill) {
        $lines[$bill[0]][$bill[1]] = $bill;
    }
    ksort($lines);
    foreach($lines as $labo) {
        ksort($labo);
        foreach($labo as $line) {
            if($_POST["type"] == "send-bills") {
                if($line[3] === "READY" || $line[3] === "ERROR") {
                    $choices[] = choice($i, $line);
                }
            }
            else {
                $lockRun = Lock::load($dir, "run");
                if( $line[3] === "SENT" || ($lockRun && $line[3] === "READY") ) {
                    $choices[] = choice($i, $line);
                }
            }

            $i++;
        }
    }
    if(count($choices)>0) {
        $html .= '<br /><span id="selected-factures">aucune facture sélectionnée</span> sur '.count($choices).'
                <div><button type="button" id="all-bills" class="btn but-line lockable">Tout sélectionner</button><input type="hidden" name="sending" id="sending" value="'.$_POST["type"].'" /></div>
                <div>';
        if(!TEST_MODE || ($superviseur->isSuperviseur($user) && !DEV_MODE)) {
            $html .= '<button type="button" id="real" class="btn but-line-red lockable">Envoyer en facturation</button>';
            $html .= '<button type="button" id="pres" class="btn but-line-green lockable">Envoyer en pré-saisie</button>';

        }
        if(TEST_MODE) {
            $html .= '<button type="button" id="simu" class="btn but-line-blue lockable">Envoyer en simulation</button>';
        }

        $html .= '</div><div id="over-bills"><table class="table" id="bills">';
    
        foreach($choices as $choice) {
            $html .= $choice;
        }
        $html .= "</table></div>";
    }
    
    echo $html;
}

/**
 * Creates a table line for one bill, with checkbox and data
 *
 * @param integer $i bill id
 * @param array $line bill data as array
 * @return string
 */
function choice(int $i, array $line): string 
{
    return '<tr>
                <td>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input check-bill" id="bill-'.$i.'" name="bills" value="'.$line[1].'">
                        <label class="custom-control-label" for="bill-'.$i.'"> '.$line[0].'</label>
                    </div>
                </td>
                <td>'.$line[1].'</td>
                <td>'.number_format(floatval($line[2]), 2, ".", "'").'</td>
                <td>'.$line[3].'</td>
            </tr>';
}
