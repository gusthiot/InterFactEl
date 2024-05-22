<?php

require_once("../assets/Lock.php");
require_once("../assets/Logfile.php");
require_once("../assets/Sap.php");
require_once("../assets/Info.php");
require_once("../session.php");
require_once("../commons/Parametres.php");

if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])){
    $plateforme = $_POST["plate"];
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $version = $_POST["version"];

    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
    $lock = new Lock();
    $lock->save($dir, 'run', $lock::STATES['finalized']);
    $sep = strrpos($dir, "/");
    $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));

    $locklast = new Lock();
    $state->lastState(DATA.$plateforme, $locklast);
    if(empty($state->getLast())) {
        $dirTarifs = DATA.$plateforme."/".$year."/".$month;
        if(!Parametres::saveFirst($dir, $dirTarifs)) {
            $_SESSION['alert-danger'] = "erreur sauvegarde paramètres ";
        }   
    }
    $info = new Info();
    $content = $info->load($dir);
    if(!empty($content)) {
        $content["Closed"][2] = date('Y-m-d H:i:s');
        $content["Closed"][3] = $_SESSION['user'];
        $info->save($dir, $content);
    }
    else {
        $_SESSION['alert-warning'] = "info vide ? ";
    }
    $sap = new Sap();
    $sap->load($dir);
    $status = $sap->status();
    $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | Finalisation manuelle | ".$status." | ".$status;
    $logfile = new Logfile();
    $logfile->write(DATA.$plateforme, $txt);
    if(!empty($alert)) {
        $_SESSION['alert-warning'] = $alert;
    }
    $_SESSION['alert-success'] = "finalisé";
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
