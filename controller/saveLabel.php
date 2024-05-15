<?php

require_once("../src/Label.php");

if(isset($_POST["txt"]) && isset($_POST["dir"])){
    $label = new Label();
    $_SESSION['type'] = "alert-danger";
    if(!empty($_POST["txt"])) {
        if($label->save("../".$_POST['dir'], $_POST["txt"])) {
            $_SESSION['type'] = "alert-success";
            $_SESSION['message'] = "Label sauvegardé";
        }
        else {
            $_SESSION['message'] = "Label non-sauvegardé";
        }
    }
    else {
        if($label->remove("../".$_POST['dir'])) {
            $_SESSION['type'] = "alert-success";
            $_SESSION['message'] = "Label supprimé";
        }
        else {
            $_SESSION['message'] = "Label non-supprimé";
        }

    }
}
