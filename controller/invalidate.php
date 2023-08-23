<?php

require_once("../src/Lock.php");
require_once("../config.php");

if(isset($_GET["dir"])){
    $lock = new Lock();
    if(!$lock->save(GROUND.$_GET["dir"], "invalidate")) {
        echo "Impossible d'invalider";
    }
}