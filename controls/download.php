<?php
require_once("../commons/Zip.php");

if(isset($_GET['type'])) {
    $type = $_GET['type'];
    $tmp_file = '../tmp/'.$type.'.zip';
   
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
