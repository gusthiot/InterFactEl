<?php

if(!empty($_POST["txt"]) && !empty($_POST["dir"])){
    $txt = $_POST["txt"];
    $file = "../".$_POST['dir']."/label.txt";
    if((($open = fopen($file, "w")) !== false)) {
        if(fwrite($open, $txt) === FALSE) {
            echo "ko";
            exit;
        }
        fclose($open);
        echo "ok";
    }
}
echo "ko";