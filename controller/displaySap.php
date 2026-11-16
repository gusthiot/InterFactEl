<?php

require_once("../session.inc");
require_once("../assets/Sap.php");
require_once("../assets/ParamText.php");

/**
 * Called to display a table with the list of the bills for a run
 */
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    checkPlateforme("facturation", $_POST["plate"]);
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $sap = new Sap($dir);
    $paramtext = new ParamText();
    echo $sap->displayTable($paramtext);
}
else {
    echo "post_data_missing";
}
