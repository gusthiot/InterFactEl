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
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])){
    $plateforme = $_POST["plate"];
    checkPlateforme("facturation", $plateforme);
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $version = $_POST["version"];

    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
    $dirPrevMonth = DATA.$plateforme."/".State::getPreviousYear($year, $month)."/".State::getPreviousMonth($year, $month);

    $state = new State(DATA.$plateforme);
    $dirTarifs = DATA.$plateforme."/".$year."/".$month;
    if(empty($state->getLast())) {
        $msg = Tarifs::saveFirst($dir, $dirTarifs);
        if(!empty($msg)) {
            $_SESSION['alert-danger'] = $msg;
        }
    }

    $sap = new Sap($dir);
    $status = $sap->status();
    Lock::save($dir, 'run', Lock::STATES['finalized']);
    $sep = strrpos($dir, "/");
    Lock::save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));
    if (in_array($status, [0, 1]) && file_exists($dirPrevMonth) && !file_exists($dirPrevMonth."/".Lock::FILES['month'])) {
        $prevVersion = 0;
        foreach(globReverse($dirPrevMonth) as $dirPrevVersion) {
                if (file_exists($dirPrevVersion."/".Lock::FILES['version'])) {
                    $sep = strrpos($dirPrevVersion, "/");
                    $prevVersion = substr($dirPrevVersion, $sep+1);
                    break;
                }
        }
        Lock::save($dirPrevMonth, 'month', $prevVersion);
    }

    $infos = Info::load($dir);
    if(!empty($infos)) {
        $infos["Closed"][2] = date('Y-m-d H:i:s');
        $infos["Closed"][3] = USER;
        Info::save($dir, $infos);
    }
    else {
        $_SESSION['alert-warning'] = "info vide ? ";
    }

    if (file_exists($dirTarifs."/unused.csv")) {
        unlink($dirTarifs."/unused.csv");
    }

    $txt = date('Y-m-d H:i:s')." | ".USER." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | Finalisation manuelle | ".$status." | ".$status;
    Logfile::write(DATA.$plateforme, $txt);
    $_SESSION['alert-success'] = "finalisé";
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
