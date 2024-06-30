<?php

require_once("../assets/Lock.php");
require_once("../assets/Logfile.php");
require_once("../assets/Sap.php");
require_once("../assets/Info.php");
require_once("../includes/Params.php");
require_once("../includes/State.php");
require_once("../session.php");

checkGest($dataGest);
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])){
    
    $plateforme = $_POST["plate"];
    checkPlateforme($dataGest, $plateforme);
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $version = $_POST["version"];

    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;

    $locklast = new Lock();
    $state = new State();
    $state->lastState(DATA.$plateforme, $locklast);
    if(empty($state->getLast())) {
        $dirTarifs = DATA.$plateforme."/".$year."/".$month;
        $msg = Params::saveFirst($dir, $dirTarifs);
        if(!empty($msg)) {
            $_SESSION['alert-danger'] = $msg;
        }
    }

    $lock = new Lock();
    $lock->save($dir, 'run', $lock::STATES['finalized']);
    $sep = strrpos($dir, "/");
    $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));

    $info = new Info();
    $content = $info->load($dir);
    if(!empty($content)) {
        $content["Closed"][2] = date('Y-m-d H:i:s');
        $content["Closed"][3] = $user;
        $info->save($dir, $content);
    }
    else {
        $_SESSION['alert-warning'] = "info vide ? ";
    }
    $sap = new Sap();
    $sap->load($dir);
    $status = $sap->status();
    $txt = date('Y-m-d H:i:s')." | ".$user." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | Finalisation manuelle | ".$status." | ".$status;
    $logfile = new Logfile();
    $logfile->write(DATA.$plateforme, $txt);
    $_SESSION['alert-success'] = "finalisé";
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
