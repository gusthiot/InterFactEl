<?php

require_once("../src/Lock.php");

if(isset($_GET["dir"])){
    $lock = new Lock();
    if(!$lock->save("../".$_GET["dir"], "invalidate")) {
        echo "Impossible d'invalider";
    }
    else {
        echo "action effectuée";
    }
}