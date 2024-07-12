<?php

$disabled = "";
$dlTxt = "";
$lockUser = Lock::loadByName($sciper.".lock");
if(!empty($lockUser)) {
    $disabled = "disabled";
    $dlTxt = '<a href="#" id="download-prefa">Vous avez une préfacturation à télécharger avant de pouvoir en faire une nouvelle.</a>';
}

$lockProcess = Lock::load("./", "process");
$lockedPlate = "";
$lockedRun = "";
$lockedProcessus = "";
if(!empty($lockProcess)) {
    $disabled = "disabled";
    $lockedTab = explode(" ", $lockProcess);
    if($lockedTab[0] == "prefa") {
        $lockedProcessus = "Une préfacturation";
    }
    else {
        $lockedProcessus = "Un envoi SAP";
    }
    $lockedPlate = $lockedTab[1];
    $lockedRun = $lockedTab[2];
}
