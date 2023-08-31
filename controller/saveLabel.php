<?php

require_once("../src/Label.php");

if(isset($_POST["txt"]) && isset($_POST["dir"])){
    $label = new Label();
    if($label->save("../".$_POST['dir'], $_POST["txt"])) {
        echo "ok";
    }
    else {
        echo "ko";
    }
}