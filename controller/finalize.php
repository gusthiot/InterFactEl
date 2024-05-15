<?php

require_once("../src/Lock.php");
require_once("../src/Logfile.php");
require_once("../src/Sap.php");
require_once("../src/Info.php");
require_once("../session.php");
require_once("../commons/Parametres.php");

if(isset($_POST["dir"]) && isset($_POST["dirTarifs"])){
    $dir = "../".$_POST["dir"];
    $lock = new Lock();
    $lock->save($dir, 'run', $lock::STATES['finalized']);
    $sep = strrpos($dir, "/");
    $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));
    $alert = "alert-success";
    $res = "";
    if(!empty($_POST["dirTarifs"])) {
        $dirTarifs = "../".$_POST["dirTarifs"];
        if(!Parametres::saveFirst($dir, $dirTarifs)) {
            $alert = "alert-danger";
            $res .= "erreur sauvegarde paramètres ";
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
        $res .= "info vide ? ";
    }
    logAction($_POST["dir"]);
    $res .= "finalisé";
    $_SESSION['type'] = $alert;
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
    $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$tab[1].", ".$tab[2].", ".$tab[3].", ".$tab[4]." | ".$tab[4]." | Finalisation manuelle | ".$status." | ".$status;
    $logfile = new Logfile();
    $logfile->write("../".$tab[0], $txt);
}
