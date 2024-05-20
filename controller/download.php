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
    elseif($type==="tarifs") {
        if(isset($_GET['plate']) && isset($_GET['year']) && isset($_GET['month'])) {            
            $fileName = "../".$_GET['plate']."/".$_GET['year']."/".$_GET['month']."/parametres.zip";
            header('Content-disposition: attachment; filename="'.basename($fileName).'"');
            header('Content-type: application/zip');
            readfile($fileName);
        }
    }
    elseif($type==="prefa") {     
        $locku = new Lock();
        $fileName = $locku->load("../", $sciper.".lock");
        if(!empty($fileName)) {   
            header('Content-disposition: attachment; filename="'.basename($fileName).'"');
            header('Content-type: application/zip');
            readfile($fileName);
            unlink($tmpFile);
            unlink("../".$sciper.".lock");
        }
    }
    else {
        $_SESSION['alert-danger'] = "erreur download";
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
