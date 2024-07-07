<?php

require_once("../assets/Label.php");
require_once("../session.inc");

checkGest($dataGest);
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["txt"])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    if(isset($_POST["version"]) && isset($_POST["run"])){
        $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    }
    else {
        $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month'];
    }
    $label = new Label();
    if(!empty($_POST["txt"])) {
        if($label->save($dir, $_POST["txt"])) {
            $_SESSION['alert-success'] = "Label sauvegardé";
        }
        else {
            $_SESSION['alert-danger'] = "Label non-sauvegardé";
        }
    }
    else {
        if($label->remove($dir)) {
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
