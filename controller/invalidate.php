<?php

require_once("../src/Lock.php");

if(!empty($_GET["dir"])){
    $lock = new Lock();
    if($lock->save($_GET["dir"], "invalidate")) {
        echo "ok";
    }
    else {
        echo "ko";
    }

}