<?php

require_once("../session.inc");

/**
 * Called to destroy all the data of a given plateform
 */
if(TEST_MODE) {
    if(isset($_GET["plate"])) {
        checkPlateforme($dataGest, "facturation", $_GET["plate"]);
        if($superviseur->isSuperviseur($user) && TEST_MODE == "TEST") { 
            exec(sprintf("rm -rf %s", escapeshellarg(DATA.$_GET["plate"])));
            $_SESSION['alert-success'] = "données de plateforme correctement effacées";
        }
        else {
            $_SESSION['alert-danger'] = "wrong place, wrong user";
        }
        header('Location: ../facturation.php?plateforme='.$_GET["plate"]);
    }
    else {
        $_SESSION['alert-danger'] = "post_data_missing";
        header('Location: ../index.php');
    }
}
else {
    $_SESSION['alert-danger'] = "only for testing";
    header('Location: ../index.php');
}
