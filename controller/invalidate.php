<?php

require_once("../assets/Lock.php");
require_once("../assets/Logfile.php");
require_once("../assets/Sap.php");
require_once("../session.inc");
require_once("../assets/Info.php");

checkGest($dataGest);
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    $plateforme = $_POST["plate"];
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $version = $_POST["version"];

    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
    $lock = new Lock();
    $lock->save($dir, 'run', $lock::STATES['invalidate']);
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
    $txt = date('Y-m-d H:i:s')." | ".$user." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | Invalidation | ".$status." | ".$status;
    $logfile = new Logfile();
    $logfile->write(DATA.$plateforme, $txt);
    $_SESSION['alert-success'] = "invalidé";
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
