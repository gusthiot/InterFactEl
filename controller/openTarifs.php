<?php

require_once("../assets/Sap.php");
require_once("../includes/Tarifs.php");
require_once("../includes/Zip.php");
require_once("../session.inc");

if(isset($_POST["plate"]) && isset($_POST["date"]) && isset($_POST["type"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $month = substr($_POST["date"], 4, 2);
    $year = substr($_POST["date"], 0, 4);
    $dir = DATA.$plateforme."/".$year."/".$month;

    $files = [];
    $type = $_POST["type"];

    if($type == "read") {
        $lastRun = 0;
        $lastVersion = 0;
        foreach(globReverse($dir) as $dirVersion) {
            foreach(globReverse($dirVersion) as $dirRun) {
                $sap = new Sap($dirRun);
                if(file_exists($dirRun."/lock.csv") || $sap->status() > 1) {
                    $lastRun = basename($dirRun);
                    $lastVersion = basename($dirVersion);
                    break;
                }
            }
            if($lastRun > 0) {
                break;
            }
        }
        $dirRun = $dir."/".$lastVersion."/".$lastRun;
        foreach(Tarifs::FILES as $file) {
            if(file_exists($dirRun."/IN/".$file)) {
                $files[$file] = base64_encode(file_get_contents($dirRun."/IN/".$file));
            }
        }
    }
    if($type == "control") {
        $zip = new ZipArchive;
        if ($zip->open($dir."/".ParamZip::NAME) === TRUE) {
            foreach(Tarifs::FILES as $file) {
                $content = $zip->getFromName($file);
                if($content) {
                    $files[$file] = base64_encode($content);
                }
            }
            $zip->close();
        }
    }

    echo json_encode($files);
}
