<?php

require_once("../src/Sap.php");
require_once("../src/Lock.php");

if(isset($_POST["dir"]) && isset($_POST["type"])){
    $sap = new Sap();
    $html = "";
    $choices = [];
    $i = 0;
    $bills = $sap->load("../".$_POST["dir"]);
    $lines = [];
    foreach($bills as $bill) {
        $lines[$bill[0]][$bill[1]] = $bill;
    }
    ksort($lines);
    foreach($lines as $labo) {
        ksort($labo);
        foreach($labo as $line) {
            if($_POST["type"] == "sendBills") {
                if($line[3] === "READY" || $line[3] === "ERROR") {
                    $choices[] = choice($i, $line);
                }
            }
            else {
                $lock = new Lock();
                $loctxt = $lock->load("../".$_POST["dir"], "run");
                if( $line[3] === "SENT" || ($loctxt && $line[3] === "READY") ) {
                    $choices[] = choice($i, $line);
                }
            }

            $i++;
        }
    }
    if(count($choices)>0) {
        $html .= '<br /><div><button type="button" id="allBills" class="btn but-line lockable">Tout sélectionner</button><button type="button" id="'.$_POST["type"].'" class="btn but-line lockable">Envoyer</button></div><div id="over-bills"><table class="table" id="bills-tab">';
    
        foreach($choices as $choice) {
            $html .= $choice;
        }
        $html .= "</table></div>";
    }
    
    echo $html;
}

function choice(int $i, array $line): string 
{
    return '<tr>
                <td>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="bill'.$i.'" name="bills" value="'.$line[1].'">
                        <label class="custom-control-label" for="bill'.$i.'"> '.$line[0].'</label>
                    </div>
                </td>
                <td>'.$line[1].'</td>
                <td>'.number_format(floatval($line[2]), 2, ".", "'").'</td>
                <td>'.$line[3].'</td>
            </tr>';
}
