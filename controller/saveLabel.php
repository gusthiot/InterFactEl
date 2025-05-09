<?php

require_once("../assets/Label.php");
require_once("../session.inc");

/**
 * Called to save a new label, or delete it
 */
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["txt"]) && isset($_POST["right"])) {
    checkPlateforme($_POST["right"], $_POST["plate"]);
    if(isset($_POST["version"]) && isset($_POST["run"])){
        $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    }
    else {
        $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month'];
    }
    if(!empty($_POST["txt"])) {
        if(Label::save($dir, $_POST["txt"])) {
            $_SESSION['alert-success'] = "Label sauvegardé";
        }
        else {
            $_SESSION['alert-danger'] = "Label non-sauvegardé";
        }
    }
    else {
        if(Label::remove($dir)) {
            $_SESSION['alert-success'] = "Label supprimé";
        }
        else {
            $_SESSION['alert-danger'] = "Label non-supprimé";
        }

    }
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
}
