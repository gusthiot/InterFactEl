<?php

/**
 * checks if a process is running, and if it's the case, displays a message, and disables access to processing
 */
if(DATA_GEST) {
    $disabled = "";
    $dlTxt = "";
    $lockUser = Lock::loadByName(USER.".lock");
    if(!empty($lockUser)) {
        $disabled = "disabled";
        $dlTxt = '<a href="#" id="download-generated">Vous avez un dossier à télécharger avant de pouvoir faire d’autres actions.</a>';
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
}
