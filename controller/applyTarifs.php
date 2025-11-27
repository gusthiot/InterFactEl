<?php

require_once("../assets/Lock.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Message.php");
require_once("../includes/Zip.php");
require_once("../includes/Tarifs.php");
require_once("../includes/State.php");
require_once("../session.inc");

if(isset($_POST['plate']) && isset($_POST['type']) && isset($_POST['files'])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);
    $files = json_decode($_POST["files"]);
    $type = $_POST["type"];
    $dir = DATA.$plateforme;

    if($type == "load" && isset($_POST['date'])) {
        $month = substr($_POST["date"], 4, 2);
        $year = substr($_POST["date"], 0, 4);

    }
    elseif($type == "correct") {
        $state = new State($dir);
        $month = $state->getLastMonth();
        $year = $state->getLastYear();
    }
    else {
        $_SESSION['alert-danger'] = "post_data_missing";
        header('Location: index.php');
        exit;
    }

    $dirTarifs = $dir.'/'.$year.'/'.$month.'/';
    $tmpDir = TEMP.'tarifs_'.time().'/';
    if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
        foreach($files as $file => $content) {
            file_put_contents($tmpDir.$file, base64_decode($content));
        }
    }
    $msg = Tarifs::createZip($dirTarifs, $tmpDir);
    State::delDir($tmpDir);
    echo ($msg == "" ? "ok" : $msg);
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
