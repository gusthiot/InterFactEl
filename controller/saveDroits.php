<?php

require_once("../assets/Sap.php");
require_once("../includes/Zip.php");
require_once("../includes/State.php");
require_once("../session.inc");

if(IS_SUPER) {
    if(isset($_POST["files"])) {
        $files = json_decode($_POST["files"]);
        $tmpDir = TEMP.'droits_'.time().'/';
        if(file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
            foreach($files as $file => $content) {
                file_put_contents($tmpDir.$file, base64_decode($content));
            }
        }

        $tmpFile = TEMP.'droits_'.time().'.zip';
        $zip = new ZipArchive;
        if($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach($files as $file => $content) {
                $zip->addFile($tmpDir.$file, $file);
            }
            if($zip->close()) {
                echo $tmpFile;
            }
        }
        State::delDir($tmpDir);
    }
}
