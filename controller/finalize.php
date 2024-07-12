<?php

require_once("../assets/Lock.php");
require_once("../assets/Logfile.php");
require_once("../assets/Sap.php");
require_once("../assets/Info.php");
require_once("../includes/Tarifs.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called to manually finalize a run
 */
checkGest($dataGest);
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])){
    
    $plateforme = $_POST["plate"];
    checkPlateforme($dataGest, $plateforme);
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $version = $_POST["version"];

    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;

    $state = new State();
    $state->lastState(DATA.$plateforme);
    if(empty($state->getLast())) {
        $dirTarifs = DATA.$plateforme."/".$year."/".$month;
        $msg = Tarifs::saveFirst($dir, $dirTarifs);
        if(!empty($msg)) {
            $_SESSION['alert-danger'] = $msg;
        }
    }

    Lock::save($dir, 'run', Lock::STATES['finalized']);
    $sep = strrpos($dir, "/");
    Lock::save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));

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
    $txt = date('Y-m-d H:i:s')." | ".$user." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | Finalisation manuelle | ".$status." | ".$status;
    Logfile::write(DATA.$plateforme, $txt);
    $_SESSION['alert-success'] = "finalisé";
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
