<?php

require_once("../src/Label.php");

if(!empty($_POST["txt"]) && !empty($_POST["dir"])){
    $label = new Label();
    if($label->save($_POST['dir'], $_POST["txt"])) {
        echo "ok";
    }
    else {
        echo "ko";
    }
}
echo "ko";