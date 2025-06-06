<?php

require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../assets/Csv.php");
require_once("../assets/Message.php");
require_once("../includes/Zip.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called to get reporting, as a zip file from concatenate bilans & stats csv
 */
if(isset($_GET["from"]) && isset($_GET["to"]) && isset($_GET["plate"])) {
    $plateforme = $_GET["plate"];
    checkPlateforme("reporting", $plateforme);
    $abrev = DATA_GEST['reporting'][$plateforme];
    $date = $_GET["from"];
    $factel = "";
    while(true) {
        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);
        $dir = DATA.$plateforme."/".$year."/".$month;
        $dirVersion = array_reverse(glob($dir."/*", GLOB_ONLYDIR))[0];
        $run = Lock::load($dirVersion, "version");

        $infos = Info::load($dirVersion."/".$run);
        if(empty($factel)) {
            $factel = $infos["FactEl"][2];
        }
        elseif($infos["FactEl"][2] != $factel) {
            $_SESSION['alert-danger'] = "Sélectionner la période pour une même version logicielle";
            header('Location: ../reporting.php?plateforme='.$plateforme);
            exit;
        }

        if($date == $_GET["to"]) {
            break;
        }

        if($month == "12") {
            $date += 89;
        }
        else {
            $date++;
        }
    }

    $noms = ["Bilan-annulé", "Bilan-conso-propre", "Bilan-factures", "Bilan-subsides", "Bilan-usage", "Stat-client", "Stat-machine", "Stat-nbre-user", "Stat-user", "Transaction1", "Transaction2", "Transaction3"];
    $info = "";
    $suf_fin = "_".$abrev."_".substr($_GET["from"], 0, 4)."_".substr($_GET["from"], 4, 2)."_".substr($_GET["to"], 0, 4)."_".substr($_GET["to"], 4, 2).".csv";    
    $tmpDir = TEMP.'reporting_'.time().'/';

    foreach($noms as $nom) {
        $date = $_GET["from"];
        $first = true;
        while(true) {
            $content = [];
            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);
            $dir = DATA.$plateforme."/".$year."/".$month;
            $dirVersion = array_reverse(glob($dir."/*", GLOB_ONLYDIR))[0];
            $run = Lock::load($dirVersion, "version");

            $suf = "_".$abrev."_".$year."_".$month."_".basename($dirVersion).".csv";
            
            $path = $dirVersion."/".$run."/Bilans_Stats/".$nom.$suf;
            $csv = Csv::extract($path);
            if(!empty($csv)) {
                if($first) {
                    $content[] = explode(";", $csv[0]);
                    $first = false;
                }
                for($i=1;$i<count($csv);$i++) {
                    $content[] = explode(";", $csv[$i]);
                }
            }
            else {
                $info .= $path." empty ? <br />";
            }


            if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                Csv::append($tmpDir.$nom.$suf_fin, $content);
            }

            if($date == $_GET["to"]) {
                break;
            }

            if($month == "12") {
                $date += 89;
            }
            else {
                $date++;
            }
        }

    }

    $zip = $user.'_reporting.zip';
    Lock::saveByName("../".USER.".lock", TEMP.$zip);
    Zip::setZipDir(TEMP.$zip, $tmpDir);
    State::delDir($tmpDir);

    if(!empty($info)) {
        $_SESSION['alert-info'] = $info;

    }
    $messages = new Message();
    $_SESSION['alert-success'] = $messages->getMessage('msg2');
    header('Location: ../reporting.php?plateforme='.$plateforme);
} 
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
