<?php

$disabled = "";
$dlTxt = "";
$locku = new Lock();
$lockedUser = $locku->loadByName($sciper.".lock");
if(!empty($lockedUser)) {
    $disabled = "disabled";
    $dlTxt = '<a href="#" id="dl_prefa">Vous avez une préfacturation à télécharger avant de pouvoir en faire une nouvelle.</a>';
}

$lockp = new Lock();
$lockedTxt = $lockp->load("./", "process");
$lockedPlate = "";
$lockedRun = "";
$lockedProcess = "";
if(!empty($lockedTxt)) {
    $disabled = "disabled";
    $lockedTab = explode(" ", $lockedTxt);
    if($lockedTab[0] == "prefa") {
        $lockedProcess = "Une préfacturation";
    }
    else {
        $lockedProcess = "Un envoi SAP";
    }
    $lockedPlate = $lockedTab[1];
    $lockedRun = $lockedTab[2];
}


?>
