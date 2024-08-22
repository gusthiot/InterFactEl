<?php

require_once("../session.inc");
require_once("../assets/Sap.php");

/**
 * Called to display a table with the list of the bills for a run
 */
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    checkPlateforme($dataGest, "facturation", $_POST["plate"]);
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $sap = new Sap($dir);
    $html = '<div id="report">';
    foreach(scandir($dir) as $i=>$file) {
        if(str_contains($file, "sap_")) {
            $pre = explode('.', $file);
            $tab = explode('_', $pre[0]);
            $html .= '<button class="collapse-title collapse-title-desktop collapsed" type="button" data-toggle="collapse" data-target="#collapse-'.$i.'" aria-expanded="false" aria-controls="collapse-'.$i.'">'.$tab[2]." ".$tab[3]." ".$tab[1]."</button>";
            $sap = new Sap($dir, $file);
            $html .= '<div class="collapse collapse-item collapse-item-desktop" id="collapse-'.$i.'"><p>'.$sap->displayTable('id="get-report" data-name="'.$file.'"').'</p></div>';
        }
    }
    $html .= '</div>';
    echo $html;
}
else {
    echo "post_data_missing";
}
