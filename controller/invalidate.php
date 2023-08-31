<?php

require_once("../src/Lock.php");
require_once("../src/Logfile.php");
require_once("../src/Sap.php");
require_once("../session.php");

if(isset($_POST["dir"])){
    $lock = new Lock();
    $lock->save("../".$_POST['dir'], 'run', "invalidate");
    logAction($_POST['dir']);
    echo "invalidé";
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