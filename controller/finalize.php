<?php

require_once("../src/Lock.php");
require_once("../src/Logfile.php");
require_once("../src/Sap.php");
require_once("../session.php");

if(isset($_POST["dir"])){
    $dir = "../".$_POST["dir"];
    $lock = new Lock();
    $lock->save($dir, 'run', "finalized");
    $sep = strrpos($dir, "/");
    $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));
    logAction($_POST["dir"]);
    echo "finalisé";
}


function logAction($dir) {
    $sap = new Sap();
    $sap->load("../".$dir);
    $status = $sap->status();
    $tab = explode("/", $path);
    $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$tab[1].", ".$tab[2].", ".$tab[3].", ".$tab[4]." | ".$tab[4]." | Finalisation manuelle | ".$status." | ".$status;
    $logfile = new Logfile();
    $logfile->write("../".$tab[0], $txt);
}