<?php

require_once("../src/Lock.php");
require_once("../src/Logfile.php");
require_once("../src/Sap.php");
require_once("../src/Info.php");
require_once("../session.php");
require_once("../commons/Parametres.php");

if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])){
    $plateforme = $_POST["plate"];
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $version = $_POST["version"];

    $dir = "../".$plateforme."/".$year."/".$month."/".$version."/".$run;
    $lock = new Lock();
    $lock->save($dir, 'run', $lock::STATES['finalized']);
    $sep = strrpos($dir, "/");
    $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));

    $locklast = new Lock();
    $state->lastState("../".$plateforme, $locklast);
    if(empty($state->getLast())) {
        $dirTarifs = "../".$plateforme."/".$year."/".$month;
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
    $inter = $year.", ".$month.", ".$version.", ".$run." | ".$run;
    logAction($dir, $inter, $plateforme);
    if(!empty($alert)) {
        $_SESSION['alert-warning'] = $alert;
    }
    $_SESSION['alert-success'] = "finalisé";
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}


function logAction($dir, $inter, $plateforme) {
    $sap = new Sap();
    $sap->load($dir);
    $status = $sap->status();
    $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$inter." | Finalisation manuelle | ".$status." | ".$status;
    $logfile = new Logfile();
    $logfile->write("../".$plateforme, $txt);
}
