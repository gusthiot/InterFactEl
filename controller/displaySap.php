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
    echo $sap->displayTable();
}
else {
    echo "post_data_missing";
}
