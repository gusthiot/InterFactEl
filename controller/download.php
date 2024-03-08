<?php
require_once("../commons/Zip.php");
require_once("../src/Paramedit.php");
require_once("../src/Paramtext.php");
require_once("../src/Lock.php");
require_once("../config.php");
require_once("../session.php");

if(isset($_GET['type'])) {
    $type = $_GET['type'];
    $tmpFile = TEMP.$type.'.zip';
   
    if($type==="config") {
        Zip::getZipDir($tmpFile, "../CONFIG/");
    }
    elseif($type==="bilans") {
        if(isset($_GET['dir'])) {
            Zip::getZipDir($tmpFile, "../".$_GET['dir']."/Bilans_Stats/");
        }
    }
    elseif($type==="annexes") {
        if(isset($_GET['dir'])) {
            Zip::getZipDir($tmpFile, "../".$_GET['dir']."/Annexes_CSV/");
        }
    }
    elseif($type==="all") {
        if(isset($_GET['dir'])) {
            Zip::getZipDir($tmpFile, "../".$_GET['dir']."/");
        }
    }
    elseif($type==="prepa") {
        if(isset($_GET['plate']) && isset($_GET['tyfact'])) {
            $lockv = new Lock();
            $state->lastState("../".$_GET['plate'], $lockv);
            
            $dir = "../".$_GET['plate']."/".$state->getLastYear()."/".$state->getLastMonth()."/".$state->getLastVersion()."/".$state->getLastRun();
            $tmpPe = TEMP.'paramedit.csv';
            $wm = "";
            $tyfact = "SAP";
            if($_GET['tyfact'] == "PROFORMA") {
                $paramtext = new Paramtext();
                if($paramtext->load($dir."/OUT/"."paramtext.csv")) {
                    $wm = $paramtext->getParam('filigr-prof');
                }
                $tyfact = "PROFORMA";
            }
            if($_GET['tyfact'] == "REDO") {
                $year = $state->getLastYear();
                $month = $state->getLastMonth();
            }
            else {
                $year = $state->getNextYear();
                $month = $state->getNextMonth();
            }
            $array = [["Platform", $_GET['plate']], ["Year", $year], ["Month", $month], ["Type", $tyfact], ["Watermark", $wm]];
            $paramedit = new Paramedit();
            $paramedit->write($tmpPe, $array);
            Zip::getZipDir($tmpFile, $dir."/OUT/", $tmpPe);
            unlink($tmpPe);
        }
    }
    elseif($type==="sap") {
        if(isset($_GET['dir'])) {
            readCsv("../".$_GET['dir']."/sap.csv");
        }
    }
    elseif($type==="modif") {
        if(isset($_GET['dir']) && isset($_GET['name'])) {
            readCsv("../".$_GET["dir"]."/".$_GET["name"].".csv");
        }
    }
    else {
        $_SESSION['message'] = "erreur";
        header('Location: ../index.php');
    }
}

function readCsv($fileName) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
    header('Content-Length: ' . filesize($fileName));
    readfile($fileName);

}

?>
