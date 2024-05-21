<?php

require_once("../assets/Lock.php");
require_once("../assets/Logfile.php");
require_once("../assets/Sap.php");
require_once("../session.php");
require_once("../assets/Info.php");

if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    $plateforme = $_POST["plate"];
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $version = $_POST["version"];

    $dir = "../".$plateforme."/".$year."/".$month."/".$version."/".$run;$lock = new Lock();
    $lock->save($dir, 'run', $lock::STATES['invalidate']);
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
    $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | Invalidation | ".$status." | ".$status;
    $logfile = new Logfile();
    $logfile->write("../".$plateforme, $txt);
    $_SESSION['alert-success'] = "invalidé";
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
