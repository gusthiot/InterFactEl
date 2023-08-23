<?php
require_once("../commons/Zip.php");
require_once("../src/Paramedit.php");
require_once("../src/Paramtext.php");
require_once("../config.php");

if(isset($_GET['type'])) {
    $type = $_GET['type'];
    $tmp_file = TEMP.$type.'.zip';
   
    if($type=="config") {
        Zip::getZipDir($tmp_file, "../CONFIG/");
    }
    if($type=="bilans") {
        if(isset($_GET['dir'])) {
            Zip::getZipDir($tmp_file, "../".$_GET['dir']."/Bilans_Stats/");
        }
    }
    if($type=="annexes") {
        if(isset($_GET['dir'])) {
            Zip::getZipDir($tmp_file, "../".$_GET['dir']."/Annexes_CSV/");
        }
    }
    if($type=="all") {
        if(isset($_GET['dir'])) {
            Zip::getZipDir($tmp_file, "../".$_GET['dir']."/");
        }
    }
    if($type=="prepa") {
        if(isset($_GET['prepa']) && isset($_GET['plate']) && isset($_GET['tyfact'])) {
            $prepa = json_decode($_GET['prepa']);
            $dir = "../".$_GET['plate']."/".$prepa->year."/".$prepa->month."/".$prepa->version."/".$prepa->run;
            $tmp_pe = TEMP.'paramedit.csv';
            $wm = "";
            $tyfact = "SAP";
            if($_GET['tyfact'] == "proforma") {
                $paramtext = new Paramtext($dir."/OUT/"."paramtext.csv");
                $wm = $paramtext->getParam('filigr-prof');
                $tyfact = "PROFORMA";
            }
            $array = [["Platform", $_GET['plate']], ["Year", $prepa->exp_y], ["Month", $prepa->exp_m], ["Type", $tyfact], ["Watermark", $wm]];
            $paramedit = new Paramedit();
            $paramedit->write($tmp_pe, $array);
            Zip::getZipDir($tmp_file, $dir."/", $tmp_pe);
            unlink($tmp_pe);
        }
    }
    if($type=="sap") {
        if(isset($_GET['dir'])) {
            $filename = "../".$_GET['dir']."/sap.csv";
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');
            header('Content-Length: ' . filesize($filename));
            readfile($filename);
        }
    }
}


?>
