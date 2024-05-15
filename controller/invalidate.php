<?php

require_once("../src/Lock.php");
require_once("../src/Logfile.php");
require_once("../src/Sap.php");
require_once("../session.php");
require_once("../src/Info.php");

if(isset($_POST["dir"])){
    $dir = "../".$_POST["dir"];
    $lock = new Lock();
    $lock->save($dir, 'run', $lock::STATES['invalidate']);
    $info = new Info();
    $content = $info->load($dir);
    $res = "";
    if(!empty($content)) {
        $content["Closed"][2] = date('Y-m-d H:i:s');
        $content["Closed"][3] = $_SESSION['user'];
        $info->save($dir, $content);
    }
    else {
        $res .= "info vide ? ";
    }
    logAction($_POST["dir"]);
    $res .= "invalidé";
    $_SESSION['type'] = "alert-success";
    $_SESSION['message'] = $res;
}
else {
    $_SESSION['type'] = "alert-danger";
    $_SESSION['message'] = "post_data_missing";
    header('Location: ../index.php');
}

function logAction($dir) {
    $sap = new Sap();
    $sap->load("../".$dir);
    $status = $sap->status();
    $tab = explode("/", $dir);
    $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$tab[1].", ".$tab[2].", ".$tab[3].", ".$tab[4]." | ".$tab[4]." | Invalidation | ".$status." | ".$status;
    $logfile = new Logfile();
    $logfile->write("../".$tab[0], $txt);
}
