<?php

require_once("../src/Label.php");

if(isset($_POST["txt"]) && isset($_POST["dir"])){
    $label = new Label();
    if(!empty($_POST["txt"])) {
        if($label->save("../".$_POST['dir'], $_POST["txt"])) {
            $_SESSION['alert-success'] = "Label sauvegardé";
        }
        else {
            $_SESSION['alert-danger'] = "Label non-sauvegardé";
        }
    }
    else {
        if($label->remove("../".$_POST['dir'])) {
            $_SESSION['alert-success'] = "Label supprimé";
        }
        else {
            $_SESSION['alert-danger'] = "Label non-supprimé";
        }

    }
}
