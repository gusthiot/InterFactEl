<?php

require_once("../src/Label.php");
require_once("../config.php");

if(isset($_POST["txt"]) && isset($_POST["dir"])){
    $label = new Label();
    if($label->save(GROUND.$_POST['dir'], $_POST["txt"])) {
        echo "ok";
    }
    else {
        echo "ko";
    }
}
echo "ko";