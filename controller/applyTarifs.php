<?php

require_once("../assets/Sap.php");
require_once("../assets/Lock.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Message.php");
require_once("../assets/Version.php");
require_once("../includes/Zip.php");
require_once("../includes/Tarifs.php");
require_once("../includes/State.php");
require_once("../session.inc");


if(isset($_POST['plate']) && isset($_POST['files']) && isset($_POST['date'])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);
    $files = json_decode($_POST["files"]);
    $dir = DATA.$plateforme;
    $month = substr($_POST["date"], 4, 2);
    $year = substr($_POST["date"], 0, 4);
    $messages = new Message();
    $msg = "";

    $dirTarifs = $dir.'/'.$year.'/'.$month.'/';
    $tmpDir = TEMP.'tarifs_'.time().'/';
    if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
        foreach($files as $file => $content) {
            file_put_contents($tmpDir.$file, base64_decode($content));
        }
    }
    if (file_exists($dirTarifs) || mkdir($dirTarifs, 0755, true)) {
        if (($open = fopen($dirTarifs."unused.csv", 'w')) !== false) {
            $version = Version::load('../');
            $vl = $version["version-logiciel"][2];
            if(fwrite($open, $vl) === false) {
                $msg += "impossible d'écrire dans unused.csv";
            }
            fclose($open);
        }
        $msg += Tarifs::createZip($dirTarifs, $tmpDir);
    }
    State::delDir($tmpDir);
    echo ($msg == "" ? "ok" : $msg);
}
