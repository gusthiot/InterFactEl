<?php

require_once("../assets/Lock.php");
require_once("../assets/Logfile.php");
require_once("../assets/Sap.php");
require_once("../assets/Info.php");
require_once("../session.inc");

/**
 * Called to manually invalidate a run
 */
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    checkPlateforme($dataGest, "facturation", $_POST["plate"]);
    $plateforme = $_POST["plate"];
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $version = $_POST["version"];

    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
    Lock::save($dir, 'run', Lock::STATES['invalidate']);
    $infos = Info::load($dir);
    if(!empty($infos)) {
        $infos["Closed"][2] = date('Y-m-d H:i:s');
        $infos["Closed"][3] = $user;
        Info::save($dir, $infos);
    }
    else {
        $_SESSION['alert-warning'] = "info vide ? ";
    }
    $sap = new Sap($dir);
    $status = $sap->status();
    $txt = date('Y-m-d H:i:s')." | ".$user." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | Invalidation | ".$status." | ".$status;
    Logfile::write(DATA.$plateforme, $txt);
    $_SESSION['alert-success'] = "invalidé";
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
